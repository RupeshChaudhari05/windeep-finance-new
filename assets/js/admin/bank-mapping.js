(function (window, $) {
  $(document).ready(function () {
    console.log('Bank mapping asset loaded');

    const config = window.BANK_MAPPING_CONFIG || {};

    let currentTransaction = null;

    // Delegate click handler
    $(document).on('click', '.map-btn, .edit-map-btn', function (e) {
      e.preventDefault();
      console.log('Map button clicked!');

      const txnId = $(this).data('txn-id');
      console.log('Transaction ID:', txnId);

      const $row = $(this).closest('tr');
      console.log('Row found:', $row.length);

      // Get data from row attributes
      currentTransaction = {
        id: txnId,
        date: $row.data('date'),
        bank: $row.data('bank'),
        account: $row.data('account'),
        description: $row.data('description'),
        reference: $row.data('reference'),
        debit: $row.data('debit'),
        credit: $row.data('credit')
      };

      console.log('Transaction data:', currentTransaction);

      // Populate modal
      $('#transaction_id').val(currentTransaction.id);
      $('#txn_date').text(currentTransaction.date);
      $('#txn_bank').html(currentTransaction.bank + '<br><small>' + currentTransaction.account + '</small>');
      $('#txn_description').text(currentTransaction.description);
      $('#txn_reference').text(currentTransaction.reference || 'N/A');

      // Set amount
      let amount = '';
      let amountClass = '';
      if (parseFloat(currentTransaction.credit) > 0) {
        amount = '₹' + parseFloat(currentTransaction.credit).toFixed(2);
        amountClass = 'text-success';
      } else if (parseFloat(currentTransaction.debit) > 0) {
        amount = '₹' + parseFloat(currentTransaction.debit).toFixed(2);
        amountClass = 'text-danger';
      }

      $('#txn_amount').text(amount).removeClass('text-danger text-success').addClass(amountClass);

      // Reset form
      $('#mappingForm')[0].reset();
      // remove any existing rows and add a fresh one
      $('#mapping_rows').empty();
      mappingRowIndex = 0;
      addMappingRow();
      $('#paying_member_result').html('');
      // clear any row-scoped paid_for results
      $('#mapping_rows').find('.paid_for-result').html('');
      $('#related_account_row').hide();

      // Set transaction amounts for multi-mapping UI
      const txnAmt = parseFloat((currentTransaction.credit && currentTransaction.credit !== '') ? currentTransaction.credit : currentTransaction.debit || 0);
      window._current_txn_amount = txnAmt;
      $('#txn_amount_display').text('₹' + txnAmt.toFixed(2));
      $('#txn_amount_display_small').text('₹' + txnAmt.toFixed(2));
      updateRemaining();

      console.log('Opening modal...');
      $('#mappingModal').modal('show');
    });

    // Mapping rows and helpers
    let mappingRowIndex = 0;

    function formatCurrency(v) {
      return '₹' + parseFloat(v || 0).toFixed(2);
    }

    function addMappingRow(data = {}) {
      const i = mappingRowIndex++;
      const html = `
      <div class="mapping-row card p-2 mb-2" data-row-index="${i}">
        <div class="row">
          <div class="col-md-4">
            <label>Paid For</label>
            <div class="input-group">
              <input type="text" class="form-control member-search paid-for-search" placeholder="Member to credit">
              <div class="input-group-append">
                <button class="btn btn-info btn-search-member" data-role="paid_for" type="button"><i class="fas fa-search"></i></button>
              </div>
            </div>
            <input type="hidden" class="paid_for-member-id" name="mappings[${i}][paid_for_member_id]" value="${data.paid_for_member_id || ''}">
            <div class="paid_for-result mt-1">${data.paid_for_display || ''}</div>
          </div>
          <div class="col-md-2">
            <label>Category</label>
            <select class="form-control mapping-type" name="mappings[${i}][transaction_type]">
              <option value="">Select</option>
              <option value="emi">Loan EMI Payment</option>
              <option value="savings">Savings Deposit</option>
              <option value="fine">Fine Payment</option>
              <option value="share">Share Capital</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-3">
            <label>Related Account</label>
            <select class="form-control related-account" name="mappings[${i}][related_account]"><option value="">Select Account</option></select>
          </div>
          <div class="col-md-2">
            <label>Amount</label>
            <input type="number" step="0.01" min="0" class="form-control mapping-amount" name="mappings[${i}][amount]" value="${data.amount || ''}">
          </div>
          <div class="col-md-1">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-danger remove-mapping-row btn-block"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>
      `;

      $('#mapping_rows').append(html);
    }

    $(document).on('click', '#add_mapping_row', function () { addMappingRow(); updateRemaining(); });

    $(document).on('click', '.remove-mapping-row', function () {
      $(this).closest('.mapping-row').remove();
      updateRemaining();
    });

    function updateRemaining() {
      let sum = 0;
      $('.mapping-amount').each(function () { sum += parseFloat($(this).val() || 0); });
      const remaining = (window._current_txn_amount || 0) - sum;
      $('#txn_remaining_display').text(formatCurrency(remaining > 0 ? remaining : 0));
      $('#txn_remaining_display_small').text(formatCurrency(remaining > 0 ? remaining : 0));
    }

    // Row-scoped member search and selection
    $(document).on('click', '.btn-search-member', function () {
      const role = $(this).data('role');
      const $row = $(this).closest('.mapping-row');
      const query = $row.find('input.member-search').val().trim();
      if (!query) { alert('Please enter search term'); return; }
      searchMemberForRow(role, $row, query);
    });

    function searchMemberForRow(role, $row, query) {
      const url = config.search_members_url;
      $row.find('.' + role + '-result').html('<i class="fas fa-spinner fa-spin"></i> Searching...');

      $.ajax({
        url: url,
        type: 'POST',
        data: { search: query },
        dataType: 'json',
        success: function (response) {
          if (response.success && response.members.length > 0) {
            let html = '<div class="list-group">';
            response.members.forEach(function (member) {
              const statusClass = member.status === 'active' ? 'success' : 'secondary';
              html += `<a href="#" class="list-group-item list-group-item-action select-member-row" data-role="${role}" data-id="${member.id}" data-code="${member.member_code}" data-name="${member.first_name} ${member.last_name}">${member.member_code} - ${member.first_name} ${member.last_name} <span class="badge badge-${statusClass} float-right">${member.status}</span></a>`;
            });
            html += '</div>';
            $row.find('.' + role + '-result').html(html);
          } else {
            $row.find('.' + role + '-result').html('<div class="alert alert-warning">No members found</div>');
          }
        },
        error: function () {
          $row.find('.' + role + '-result').html('<div class="alert alert-danger">Search failed</div>');
        }
      });
    }

    $(document).on('click', '.select-member-row', function (e) {
      e.preventDefault();
      const role = $(this).data('role');
      const memberId = $(this).data('id');
      const code = $(this).data('code');
      const name = $(this).data('name');
      const $row = $(this).closest('.mapping-row');

      $row.find('input.' + role + '-member-id').val(memberId);
      $row.find('.' + role + '-result').html(`<div class="alert alert-success"><strong>${code}</strong> - ${name} <button type="button" class="close remove-selected-member" data-role="${role}"><span>&times;</span></button></div>`);

      // If paid_for selected and type requires accounts, load accounts for this row
      if (role === 'paid_for') {
        const txnType = $row.find('.mapping-type').val();
        if (txnType === 'emi' || txnType === 'savings') {
          loadMemberAccountsForRow(memberId, txnType, $row);
        }
      }
    });

    $(document).on('click', '.remove-selected-member', function () { const role = $(this).data('role'); const $row = $(this).closest('.mapping-row'); $row.find('input.' + role + '-member-id').val(''); $row.find('.' + role + '-result').html(''); });

    function loadMemberAccountsForRow(memberId, type, $row) {
      const url = config.get_member_accounts_url;
      $.ajax({
        url: url,
        type: 'POST',
        data: { member_id: memberId },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            let html = '<option value="">Select Account</option>';

            if (type === 'savings' && response.savings_accounts) {
              response.savings_accounts.forEach(function (account) {
                html += `<option value="savings_${account.id}" data-balance="${parseFloat(account.balance)}">${account.account_number} (Balance: ₹${parseFloat(account.balance).toFixed(2)})</option>`;
              });
              $row.find('.related-account').html(html);
              $row.find('.related-account').data('label', 'Savings Account');
            } else if (type === 'emi' && response.loans) {
              response.loans.forEach(function (loan) {
                html += `<option value="loan_${loan.id}" data-outstanding="${parseFloat(loan.outstanding)}">${loan.loan_number} (Outstanding: ₹${parseFloat(loan.outstanding).toFixed(2)})</option>`;
              });
              $row.find('.related-account').html(html);
              $row.find('.related-account').data('label', 'Loan Account');
            }

            $row.find('.related-account').show();
          }
        }
      });
    }

    // When mapping type changes in a row
    $(document).on('change', '.mapping-type', function () {
      const $row = $(this).closest('.mapping-row');
      const type = $(this).val();
      const paidForId = $row.find('input.paid_for-member-id').val();
      $row.find('.related-account').html('<option value="">Select Account</option>');
      if (type === 'emi' || type === 'savings') {
        if (paidForId) loadMemberAccountsForRow(paidForId, type, $row);
      } else if (type === 'fine') {
        if (paidForId) calculateFineForRow(paidForId, currentTransaction.date, $row);
      }
    });

    // When related account selected in a row, autofill amount
    $(document).on('change', '.related-account', function () {
      const $row = $(this).closest('.mapping-row');
      const val = $(this).val();
      if (!val) return;
      const parts = val.split('_');
      if (parts[0] === 'loan') {
        const outstanding = parseFloat($(this).find('option:selected').data('outstanding') || 0);
        const remaining = parseFloat($('#txn_remaining_display').text().replace(/[₹,]/g, '') || 0);
        $row.find('.mapping-amount').val(Math.min(outstanding, remaining).toFixed(2));
      } else if (parts[0] === 'savings') {
        const balance = parseFloat($(this).find('option:selected').data('balance') || 0);
        const remaining = parseFloat($('#txn_remaining_display').text().replace(/[₹,]/g, '') || 0);
        $row.find('.mapping-amount').val(Math.min(remaining, remaining).toFixed(2));
      }
      updateRemaining();
    });

    // Calculate fine amount for member as of transaction date
    function calculateFineForRow(memberId, txnDate, $row) {
      const url = config.calculate_fine_url || (window.BASE_URL + 'admin/bank/calculate_fine_due');
      $row.find('.mapping-amount').val('');
      $.ajax({
        url: url,
        type: 'POST',
        data: { member_id: memberId, as_of: txnDate },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            const due = parseFloat(response.total_due || 0);
            const remaining = parseFloat($('#txn_remaining_display').text().replace(/[₹,]/g, '') || 0);
            $row.find('.mapping-amount').val(Math.min(due, remaining).toFixed(2));
            updateRemaining();
          }
        }
      });
    }

    // Update remaining when amounts change
    $(document).on('input', '.mapping-amount', function () { updateRemaining(); });

    // Submit mapping form (now supports multiple rows)
    $(document).on('submit', '#mappingForm', function (e) {
      e.preventDefault();

      const txnId = $('#transaction_id').val();
      const mappings = [];
      $('.mapping-row').each(function () {
        const $r = $(this);
        // Use global paying member if row does not specify a paying id
        const paying_id = $('#paying_member_id').val() || $r.find('input.paying-member-id').val() || null;
        const paid_for_id = $r.find('input.paid_for-member-id').val();
        const type = $r.find('.mapping-type').val();
        const related = $r.find('.related-account').val();
        const amount = parseFloat($r.find('.mapping-amount').val() || 0);
        if (!amount || amount <= 0) return;
        mappings.push({ paying_member_id: paying_id || null, paid_for_member_id: paid_for_id || null, transaction_type: type, related_account: related || null, amount: amount });
      });

      const totalMapped = mappings.reduce((s, m) => s + (m.amount || 0), 0);
      const txnAmt = window._current_txn_amount || 0;
      if (totalMapped > txnAmt) { Swal.fire('Error', 'Mapped amounts exceed transaction amount', 'error'); return; }
      // allow partial maps, but if partial ensure user confirms
      const payload = { transaction_id: txnId, mappings: mappings, remarks: $('#remarks').val() };

      $.ajax({
        url: config.save_mapping_url,
        type: 'POST',
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN },
        data: JSON.stringify(payload),
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            Swal.fire({ icon: 'success', title: 'Mapped', text: response.message || 'Transaction mapped successfully' }).then(function () { $('#mappingModal').modal('hide'); location.reload(); });
          } else {
            Swal.fire('Error', response.message || 'Mapping failed', 'error');
          }
        },
        error: function () { Swal.fire('Error', 'Failed to save mapping. Please try again.', 'error'); }
      });
    });

    // Search member handlers
    $(document).on('click', '#search_paying_member', function () { searchMember('paying'); });
    $(document).on('keypress', '#paying_member_search', function (e) { if (e.which === 13) { e.preventDefault(); searchMember('paying'); } });
    $(document).on('click', '#search_paid_for_member', function () { searchMember('paid_for'); });
    $(document).on('keypress', '#paid_for_member_search', function (e) { if (e.which === 13) { e.preventDefault(); searchMember('paid_for'); } });

    // When selecting global paying member we should update existing rows if needed
    $(document).on('click', '#search_paying_member', function () { /* kept for backward compat */ });

    // Override select-member behavior for global paying selection
    $(document).on('click', '.select-member', function (e) {
      e.preventDefault();
      const type = $(this).data('type');
      const memberId = $(this).data('id');
      const memberCode = $(this).data('code');
      const memberName = $(this).data('name');

      if (type === 'paying') {
        $('#paying_member_id').val(memberId);
        $('#paying_member_result').html(
          `<div class="alert alert-success"><strong>${memberCode}</strong> - ${memberName} <button type="button" class="close" id="remove_paying_member"><span>&times;</span></button></div>`
        );
        return;
      }

      // fallback for paid_for which will be handled per-row
      $('#' + type + '_member_id').val(memberId);
      $('#' + type + '_member_result').html(
        `<div class="alert alert-success"><strong>${memberCode}</strong> - ${memberName} <button type="button" class="close" onclick="$('#${type}_member_id').val(''); $('#${type}_member_result').html('');"><span>&times;</span></button></div>`
      );

      // If paid_for member selected and transaction type is emi/savings, load accounts
      if (type === 'paid_for') {
        const txnType = $('#transaction_type').val();
        if (txnType === 'emi' || txnType === 'savings') {
          loadMemberAccounts(memberId, txnType);
        }
      }
    });

    function searchMember(type) {
      const url = config.search_members_url;
      const search = $('#' + type + '_member_search').val().trim();
      if (!search) {
        alert('Please enter search term');
        return;
      }

      const resultDiv = '#' + type + '_member_result';
      $(resultDiv).html('<i class="fas fa-spinner fa-spin"></i> Searching...');

      $.ajax({
        url: url,
        type: 'POST',
        data: { search: search },
        dataType: 'json',
        success: function (response) {
          if (response.success && response.members.length > 0) {
            let html = '<div class="list-group">';
            response.members.forEach(function (member) {
              const statusClass = member.status === 'active' ? 'success' : 'secondary';
              html += `<a href="#" class="list-group-item list-group-item-action select-member" 
                                       data-type="${type}" 
                                       data-id="${member.id}" 
                                       data-code="${member.member_code}"
                                       data-name="${member.first_name} ${member.last_name}">
                                       <strong>${member.member_code}</strong> - ${member.first_name} ${member.last_name}
                                       <span class="badge badge-${statusClass} float-right">${member.status}</span>
                                     </a>`;
            });
            html += '</div>';
            $(resultDiv).html(html);
          } else {
            $(resultDiv).html('<div class="alert alert-warning">No members found</div>');
          }
        },
        error: function () {
          $(resultDiv).html('<div class="alert alert-danger">Search failed</div>');
        }
      });
    }

    $(document).on('click', '.select-member', function (e) {
      e.preventDefault();
      const type = $(this).data('type');
      const memberId = $(this).data('id');
      const memberCode = $(this).data('code');
      const memberName = $(this).data('name');

      $('#' + type + '_member_id').val(memberId);
      $('#' + type + '_member_result').html(
        `<div class="alert alert-success">
                    <strong>${memberCode}</strong> - ${memberName}
                    <button type="button" class="close" onclick="$('#${type}_member_id').val(''); $('#${type}_member_result').html('');">
                        <span>&times;</span>
                    </button>
                </div>`
      );

      // If paid_for member selected and transaction type is emi/savings, load accounts
      if (type === 'paid_for') {
        const txnType = $('#transaction_type').val();
        if (txnType === 'emi' || txnType === 'savings') {
          loadMemberAccounts(memberId, txnType);
        }
      }
    });

    function loadMemberAccounts(memberId, type) {
      const url = config.get_member_accounts_url;
      $.ajax({
        url: url,
        type: 'POST',
        data: { member_id: memberId },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            let html = '<option value="">Select Account</option>';

            if (type === 'savings' && response.savings_accounts) {
              response.savings_accounts.forEach(function (account) {
                html += `<option value="savings_${account.id}">
                                            ${account.account_number} (Balance: ₹${parseFloat(account.balance).toFixed(2)})
                                         </option>`;
              });
              $('#related_account_label').text('Savings Account');
            } else if (type === 'emi' && response.loans) {
              response.loans.forEach(function (loan) {
                html += `<option value="loan_${loan.id}">
                                            ${loan.loan_number} (Outstanding: ₹${parseFloat(loan.outstanding).toFixed(2)})
                                         </option>`;
              });
              $('#related_account_label').text('Loan Account');
            }

            $('#related_account').html(html);
            $('#related_account_row').show();
          }
        }
      });
    }

    // Submit mapping form
    $(document).on('submit', '#mappingForm', function (e) {
      e.preventDefault();

      const url = config.save_mapping_url;
      const formData = $(this).serialize();

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            Swal.fire({ icon: 'success', title: 'Mapped', text: 'Transaction mapped successfully' }).then(function () {
              $('#mappingModal').modal('hide');
              location.reload();
            });
          } else {
            Swal.fire('Error', response.message || 'Mapping failed', 'error');
          }
        },
        error: function () {
          Swal.fire('Error', 'Failed to save mapping. Please try again.', 'error');
        }
      });
    });
  });
})(window, jQuery);
