(function (window, $) {
  $(document).ready(function () {
    console.log('Bank mapping asset loaded');

    const config = window.BANK_MAPPING_CONFIG || {};

    // Global state
    var allocations = [];
    var transactionAmount = 0;

    // Initialize Select2 for member search
    function initMemberSelect2(selector) {
      $(selector).select2({
        ajax: {
          url: config.search_members_url,
          dataType: 'json',
          delay: 250,
          data: function (params) { return { q: params.term, limit: 15 }; },
          processResults: function (resp) {
            // server returns { success, message, data }
            var items = resp && resp.data ? resp.data : [];
            return { results: items };
          }
        },
        placeholder: 'Search member by name, code, or phone...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        dropdownParent: $('#matchModal'),
        templateResult: function (item) {
          if (!item.id) return item.text;
          var label = item.full_name ? item.full_name : item.text;
          var sub = item.member_code ? (' <small class="text-muted">' + item.member_code + '</small>') : '';
          return $('<span>' + label + sub + '</span>');
        },
        templateSelection: function (item) {
          if (!item.id) return item.text || '';
          return item.full_name ? (item.member_code ? item.member_code + ' - ' + item.full_name : item.full_name) : item.text;
        }
      });
    }

    let currentTransaction = null;

    // Delegate click handler for map buttons
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

      // Determine amount
      let amount = 0;
      if (parseFloat(currentTransaction.credit) > 0) {
        amount = parseFloat(currentTransaction.credit);
      } else if (parseFloat(currentTransaction.debit) > 0) {
        amount = parseFloat(currentTransaction.debit);
      }

      // Reset modal
      allocations = [];
      transactionAmount = amount;
      $('#match_transaction_id').val(currentTransaction.id);
      $('#match_transaction_amount').val(amount);
      $('#match_amount').text('₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#match_date').text(currentTransaction.date);
      $('#match_description').text(currentTransaction.description);
      $('#paid_for_container').html('<div class="text-center text-muted py-3" id="no_members_msg"><i class="fas fa-info-circle"></i> Click "Add Member" to allocate this transaction</div>');
      $('#allocations_body').html('<tr id="no_allocations_row"><td colspan="5" class="text-center text-muted py-3">No allocations yet</td></tr>');
      $('#mapping_notes').val('');
      $('#manual_match_type').val('');
      $('#manual_amount').val('');
      $('#paying_member').val(null).trigger('change');

      updateAllocationStatus();
      initMemberSelect2('#paying_member');
      console.log('Opening modal...');
      $('#matchModal').modal('show');
    });

    // Add Paid For Member
    $('#add_paid_for_member').click(function () {
      $('#no_members_msg').hide();
      var cardId = 'member_card_' + Date.now();
      var template = $('#member_allocation_template').html();
      var $card = $(template);
      $card.attr('id', cardId);

      // Add member search
      var $searchDiv = $('<div class="mb-2"><select class="form-control member-search-select" style="width: 100%;"><option value="">Search member...</option></select></div>');
      $card.find('.card-body').prepend($searchDiv);
      $('#paid_for_container').append($card);

      // Initialize select2
      var $select = $card.find('.member-search-select');
      $select.select2({
        ajax: {
          url: config.search_members_url,
          dataType: 'json',
          delay: 300,
          data: function (params) { return { q: params.term, limit: 15 }; },
          processResults: function (resp) { return { results: resp && resp.data ? resp.data : [] }; }
        },
        placeholder: 'Search member...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        dropdownParent: $('#matchModal'),
        templateResult: function (item) {
          if (!item.id) return item.text;
          var label = item.full_name ? item.full_name : item.text;
          var sub = item.member_code ? (' <small class="text-muted">' + item.member_code + '</small>') : '';
          return $('<span>' + label + sub + '</span>');
        },
        templateSelection: function (item) {
          if (!item.id) return item.text || '';
          return item.full_name ? (item.member_code ? item.member_code + ' - ' + item.full_name : item.full_name) : item.text;
        }
      });

      // On member select, load their accounts
      $select.on('select2:select', function (e) {
        var member = e.params.data;
        $card.attr('data-member-id', member.id);
        $card.find('.member-name').text(member.full_name);
        $card.find('.member-code').text(member.member_code);
        loadMemberAccounts($card, member.id);
      });

      // Remove member card
      $card.find('.remove-member-btn').click(function () {
        var memberId = $card.attr('data-member-id');
        // Remove allocations for this member
        allocations = allocations.filter(a => a.member_id != memberId);
        $card.remove();
        updateAllocationsTable();
        updateAllocationStatus();
        if ($('#paid_for_container .member-allocation-card').length === 0) {
          $('#no_members_msg').show();
        }
      });
    });

    // Load member accounts (loans, savings, fines)
    function loadMemberAccounts($card, memberId) {
      $.get(config.get_member_details_url, { member_id: memberId }, function (response) {
        if (response.success) {
          var data = response.data;
          renderLoans($card.find('.loans-list'), data.loans, memberId);
          renderSavings($card.find('.savings-list'), data.savings, memberId);
          renderFines($card.find('.fines-list'), data.fines, memberId);
        }
      }, 'json');
    }

    function renderLoans($container, loans, memberId) {
      if (!loans || loans.length === 0) {
        $container.html('<small class="text-muted">No active loans</small>');
        return;
      }
      var html = '';
      loans.forEach(function (loan) {
        html += '<div class="border rounded p-2 mb-1 loan-item" data-loan-id="' + loan.id + '">';
        html += '<div class="d-flex justify-content-between"><strong>' + loan.loan_number + '</strong><span class="badge badge-info">₹' + parseFloat(loan.pending_amount).toLocaleString('en-IN') + '</span></div>';
        if (loan.installments && loan.installments.length > 0) {
          html += '<div class="mt-1">';
          loan.installments.forEach(function (inst) {
            html += '<div class="d-flex justify-content-between align-items-center py-1 installment-row" data-inst-id="' + inst.id + '">';
            html += '<small>EMI #' + inst.installment_number + ' - Due: ' + inst.due_date + '</small>';
            html += '<div class="input-group input-group-sm" style="width: 120px;">';
            html += '<input type="number" class="form-control allocation-input" data-type="emi" data-member="' + memberId + '" data-related="loan_' + inst.id + '" data-label="EMI #' + inst.installment_number + ' (' + loan.loan_number + ')" placeholder="₹" step="0.01" min="0" max="' + inst.pending_amount + '">';
            html += '</div></div>';
          });
          html += '</div>';
        }
        html += '</div>';
      });
      $container.html(html);
    }

    function renderSavings($container, savings, memberId) {
      if (!savings || savings.length === 0) {
        $container.html('<small class="text-muted">No savings accounts</small>');
        return;
      }
      var html = '';
      savings.forEach(function (acc) {
        html += '<div class="border rounded p-2 mb-1">';
        html += '<div class="d-flex justify-content-between"><strong>' + acc.account_number + '</strong><span class="badge badge-success">₹' + parseFloat(acc.current_balance).toLocaleString('en-IN') + '</span></div>';
        html += '<div class="input-group input-group-sm mt-1">';
        html += '<input type="number" class="form-control allocation-input" data-type="savings" data-member="' + memberId + '" data-related="savings_' + acc.id + '" data-label="Savings ' + acc.account_number + '" placeholder="Deposit amount" step="0.01" min="0">';
        html += '</div></div>';
      });
      $container.html(html);
    }

    function renderFines($container, fines, memberId) {
      if (!fines || fines.length === 0) {
        $container.html('<small class="text-muted">No pending fines</small>');
        return;
      }
      var html = '';
      fines.forEach(function (fine) {
        html += '<div class="border rounded p-2 mb-1">';
        html += '<div class="d-flex justify-content-between"><small>' + fine.fine_type + '</small><span class="badge badge-danger">₹' + parseFloat(fine.pending_amount).toLocaleString('en-IN') + '</span></div>';
        html += '<div class="input-group input-group-sm mt-1">';
        html += '<input type="number" class="form-control allocation-input" data-type="fine" data-member="' + memberId + '" data-related="fine_' + fine.id + '" data-label="Fine: ' + fine.fine_type + '" placeholder="Pay amount" step="0.01" min="0" max="' + fine.pending_amount + '">';
        html += '</div></div>';
      });
      $container.html(html);
    }

    // Handle allocation input changes
    $(document).on('input', '.allocation-input', function () {
      var $input = $(this);
      var amount = parseFloat($input.val()) || 0;
      var type = $input.data('type');
      var memberId = $input.data('member');
      var related = $input.data('related');
      var label = $input.data('label');

      // Remove existing allocation for this input
      allocations = allocations.filter(a => !(a.member_id == memberId && a.related == related));

      // Add new allocation if amount > 0
      if (amount > 0) {
        allocations.push({
          member_id: memberId,
          type: type,
          related: related,
          label: label,
          amount: amount
        });
      }

      updateAllocationsTable();
      updateAllocationStatus();
    });

    // Add manual/internal entry
    $('#add_manual_entry').click(function () {
      var type = $('#manual_match_type').val();
      var amount = parseFloat($('#manual_amount').val()) || transactionAmount - getTotalAllocated();

      if (!type) {
        toastr.warning('Please select an internal entry type');
        return;
      }
      if (amount <= 0) {
        toastr.warning('Amount must be greater than 0');
        return;
      }

      allocations.push({
        member_id: null,
        type: type,
        related: null,
        label: type.replace('_', ' ').toUpperCase(),
        amount: amount
      });

      $('#manual_match_type').val('');
      $('#manual_amount').val('');
      updateAllocationsTable();
      updateAllocationStatus();
    });

    function updateAllocationsTable() {
      var $tbody = $('#allocations_body');
      $tbody.empty();

      if (allocations.length === 0) {
        $tbody.html('<tr id="no_allocations_row"><td colspan="5" class="text-center text-muted py-3">No allocations yet</td></tr>');
        return;
      }

      allocations.forEach(function (alloc, index) {
        var memberName = alloc.member_id ? ($('.member-allocation-card[data-member-id="' + alloc.member_id + '"] .member-name').text() || 'Member #' + alloc.member_id) : '-';
        var row = '<tr data-index="' + index + '">';
        row += '<td>' + memberName + '</td>';
        row += '<td><span class="badge badge-' + getTypeBadgeClass(alloc.type) + '">' + alloc.type.toUpperCase() + '</span></td>';
        row += '<td>' + alloc.label + '</td>';
        row += '<td class="text-right">₹' + alloc.amount.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</td>';
        row += '<td><button type="button" class="btn btn-xs btn-danger remove-allocation-btn" data-index="' + index + '"><i class="fas fa-times"></i></button></td>';
        row += '</tr>';
        $tbody.append(row);
      });
    }

    function getTypeBadgeClass(type) {
      switch (type) {
        case 'emi': case 'loan_payment': return 'primary';
        case 'savings': return 'success';
        case 'fine': return 'danger';
        case 'expense': case 'bank_charge': return 'warning';
        case 'disbursement': return 'info';
        case 'internal_transfer': case 'contra_entry': return 'dark';
        default:
          if (type && type.indexOf('other_fee_') === 0) return 'info';
          return 'secondary';
      }
    }

    // Remove allocation
    $(document).on('click', '.remove-allocation-btn', function () {
      var index = $(this).data('index');
      var alloc = allocations[index];
      allocations.splice(index, 1);

      // Clear the input if it was from a member card
      if (alloc.member_id && alloc.related) {
        $('.allocation-input[data-member="' + alloc.member_id + '"][data-related="' + alloc.related + '"]').val('');
      }

      updateAllocationsTable();
      updateAllocationStatus();
    });

    function getTotalAllocated() {
      return allocations.reduce((sum, a) => sum + a.amount, 0);
    }

    function updateAllocationStatus() {
      var total = getTotalAllocated();
      var remaining = transactionAmount - total;
      var percent = transactionAmount > 0 ? (total / transactionAmount) * 100 : 0;

      $('#total_allocated').text('₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#remaining_amount').text('₹' + remaining.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#footer_total').text('₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#allocation_progress').css('width', Math.min(percent, 100) + '%');

      var $status = $('#allocation_status');
      var $error = $('#validation_error');
      var $btn = $('#confirmMatch');

      if (total > transactionAmount) {
        $status.removeClass('alert-info alert-success').addClass('alert-danger');
        $error.show();
        $('#validation_msg').text('Total allocated (₹' + total.toLocaleString('en-IN') + ') exceeds transaction amount (₹' + transactionAmount.toLocaleString('en-IN') + ')');
        $btn.prop('disabled', true);
      } else if (allocations.length === 0) {
        $status.removeClass('alert-danger alert-success').addClass('alert-info');
        $error.hide();
        $btn.prop('disabled', true);
      } else {
        $status.removeClass('alert-danger alert-info').addClass('alert-success');
        $error.hide();
        $btn.prop('disabled', false);
      }
    }

    // Confirm and save mapping
    $('#confirmMatch').click(function () {
      var transactionId = $('#match_transaction_id').val();
      var payingMemberId = $('#paying_member').val();
      var notes = $('#mapping_notes').val();

      if (allocations.length === 0) {
        toastr.error('Please add at least one allocation');
        return;
      }

      var total = getTotalAllocated();
      if (total > transactionAmount) {
        toastr.error('Total allocated exceeds transaction amount');
        return;
      }

      // Build mappings array
      var mappings = allocations.map(function (a) {
        return {
          paying_member_id: payingMemberId,
          paid_for_member_id: a.member_id,
          transaction_type: a.type,
          related_account: a.related,
          amount: a.amount,
          remarks: notes
        };
      });

      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

      $.ajax({
        url: config.save_mapping_url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          transaction_id: transactionId,
          mappings: mappings,
          remarks: notes
        }),
        success: function (response) {
          if (response.success) {
            toastr.success(response.message || 'Transaction mapped successfully');
            $('#matchModal').modal('hide');
            location.reload();
          } else {
            toastr.error(response.message || 'Failed to save mapping');
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Save Mapping');
          }
        },
        error: function () {
          toastr.error('An error occurred. Please try again.');
          $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Save Mapping');
        }
      });
    });

    // ============================================================
    // VIEW MAPPING DETAILS (for mapping.php page)
    // ============================================================
    $(document).on('click', '.view-mapping-btn', function (e) {
      e.preventDefault();
      var txnId = $(this).data('txn-id');
      if (!config.get_mapping_details_url) return;

      $('#mapping_detail_content').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
      $('#mappingDetailModal').modal('show');

      $.get(config.get_mapping_details_url, { transaction_id: txnId }, function (resp) {
        if (resp.success && resp.data) {
          var data = resp.data;
          var txn = data.transaction;
          var html = '';

          html += '<div class="alert alert-light border py-2">';
          html += '<div class="d-flex justify-content-between">';
          html += '<div><strong>Date:</strong> ' + (txn.date || txn.transaction_date || '') + '</div>';
          html += '<div><strong>Amount:</strong> ₹' + parseFloat(txn.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</div>';
          html += '<div><strong>Status:</strong> <span class="badge badge-' + (txn.mapping_status === 'mapped' ? 'success' : 'warning') + '">' + (txn.mapping_status || '').toUpperCase() + '</span></div>';
          html += '</div>';
          html += '<small class="text-muted">' + (txn.description || '') + '</small>';
          html += '</div>';

          if (data.mappings && data.mappings.length > 0) {
            html += '<table class="table table-sm table-bordered table-striped mb-0">';
            html += '<thead class="thead-light"><tr><th>Member</th><th>Type</th><th>Account</th><th class="text-right">Amount</th><th>Mapped At</th><th width="90">Action</th></tr></thead>';
            html += '<tbody>';
            data.mappings.forEach(function (m) {
              var typeBadge = getTypeBadgeClass(m.mapping_type);
              html += '<tr>';
              html += '<td>' + (m.member_code ? m.member_code + ' - ' : '') + (m.member_name || m.full_name || '-') + '</td>';
              html += '<td><span class="badge badge-' + typeBadge + '">' + (m.mapping_type || '').replace(/_/g, ' ').toUpperCase() + '</span></td>';
              html += '<td><small>' + (m.account_info || m.narration || '-') + '</small></td>';
              html += '<td class="text-right">₹' + parseFloat(m.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</td>';
              html += '<td><small>' + (m.mapped_at || m.created_at || '-') + '</small></td>';
              html += '<td>';
              if (!parseInt(m.is_reversed)) {
                html += '<button class="btn btn-danger btn-xs btn-reverse-mapping" data-mapping-id="' + m.id + '"><i class="fas fa-undo"></i> Reverse</button>';
              } else {
                html += '<span class="badge badge-danger">Reversed</span>';
              }
              html += '</td></tr>';
            });
            html += '</tbody></table>';
          } else {
            html += '<div class="text-center text-muted py-3">No active mappings found</div>';
          }

          $('#mapping_detail_content').html(html);
        } else {
          $('#mapping_detail_content').html('<div class="text-center text-danger py-3">Failed to load details</div>');
        }
      }, 'json').fail(function () {
        $('#mapping_detail_content').html('<div class="text-center text-danger py-3">Error loading details</div>');
      });
    });

    // Reverse mapping
    $(document).on('click', '.btn-reverse-mapping', function () {
      var mappingId = $(this).data('mapping-id');
      if (!config.reverse_mapping_url) return;
      var reason = prompt('Reason for reversal:');
      if (!reason) return;

      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.post(config.reverse_mapping_url, { mapping_id: mappingId, reason: reason }, function (resp) {
        if (resp.success) {
          toastr.success(resp.message);
          $('#mappingDetailModal').modal('hide');
          setTimeout(function () { location.reload(); }, 800);
        } else {
          toastr.error(resp.message || 'Failed to reverse mapping');
          $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> Reverse');
        }
      }, 'json');
    });

    // ============================================================
    // DISBURSEMENT MAPPING
    // ============================================================
    var selectedLoanId = null;

    $(document).on('click', '.btn-disbursement', function () {
      var txnId = $(this).data('txn-id') || $(this).data('id');
      var amount = parseFloat($(this).data('amount') || $(this).closest('tr').data('debit') || 0);
      selectedLoanId = null;

      $('#disb_transaction_id').val(txnId);
      $('#disb_amount').text('₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#disb_amount_input').val(amount);
      $('#disb_loans_container').hide();
      $('#disb_loans_list').empty();
      $('#disb_remarks').val('');
      $('#confirmDisbursement').prop('disabled', true);

      if ($('#disb_member_search').data('select2')) {
        $('#disb_member_search').select2('destroy');
      }
      $('#disb_member_search').select2({
        ajax: {
          url: config.search_members_url,
          dataType: 'json',
          delay: 300,
          data: function (params) { return { q: params.term, limit: 15 }; },
          processResults: function (resp) { return { results: resp && resp.data ? resp.data : [] }; }
        },
        placeholder: 'Search member...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        dropdownParent: $('#disbursementModal')
      }).val(null).trigger('change');

      $('#disbursementModal').modal('show');
    });

    $('#disb_member_search').on('select2:select', function (e) {
      var memberId = e.params.data.id;
      if (!config.get_disbursable_loans_url) return;
      $.get(config.get_disbursable_loans_url, { member_id: memberId }, function (resp) {
        if (resp.success && resp.data && resp.data.length > 0) {
          var html = '';
          resp.data.forEach(function (loan) {
            html += '<div class="border rounded p-2 mb-2 disb-loan-item" data-loan-id="' + loan.id + '" style="cursor: pointer;">';
            html += '<div class="d-flex justify-content-between align-items-center">';
            html += '<div><strong>' + loan.loan_number + '</strong><br><small>' + (loan.member_code || '') + ' - ' + (loan.member_name || '') + '</small></div>';
            html += '<div class="text-right"><span class="badge badge-primary">₹' + parseFloat(loan.net_disbursement || loan.loan_amount || 0).toLocaleString('en-IN') + '</span></div>';
            html += '</div></div>';
          });
          $('#disb_loans_list').html(html);
          $('#disb_loans_container').show();
        } else {
          $('#disb_loans_list').html('<div class="text-muted text-center py-2">No disbursable loans found</div>');
          $('#disb_loans_container').show();
        }
      }, 'json');
    });

    $(document).on('click', '.disb-loan-item', function () {
      $('.disb-loan-item').removeClass('border-primary bg-light');
      $(this).addClass('border-primary bg-light');
      selectedLoanId = $(this).data('loan-id');
      $('#confirmDisbursement').prop('disabled', false);
    });

    $('#confirmDisbursement').click(function () {
      if (!selectedLoanId || !config.map_disbursement_url) return;
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

      $.post(config.map_disbursement_url, {
        transaction_id: $('#disb_transaction_id').val(),
        loan_id: selectedLoanId,
        amount: $('#disb_amount_input').val(),
        remarks: $('#disb_remarks').val()
      }, function (resp) {
        if (resp.success) {
          toastr.success(resp.message);
          $('#disbursementModal').modal('hide');
          setTimeout(function () { location.reload(); }, 800);
        } else {
          toastr.error(resp.message || 'Failed');
          $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Map Disbursement');
        }
      }, 'json');
    });

    // ============================================================
    // INTERNAL TRANSACTION MAPPING
    // ============================================================
    $(document).on('click', '.btn-internal', function () {
      var txnId = $(this).data('txn-id') || $(this).data('id');
      var amount = parseFloat($(this).data('amount') || $(this).closest('tr').data('debit') || $(this).closest('tr').data('credit') || 0);

      $('#int_transaction_id').val(txnId);
      $('#int_amount_display').text('₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
      $('#int_amount').val(amount);
      $('#int_type').val('');
      $('#int_desc').val('');
      $('#internalModal').modal('show');
    });

    $('#confirmInternal').click(function () {
      var type = $('#int_type').val();
      var amount = parseFloat($('#int_amount').val());
      if (!type) { toastr.warning('Select a type'); return; }
      if (!amount || !config.map_internal_url) return;

      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.post(config.map_internal_url, {
        transaction_id: $('#int_transaction_id').val(),
        type: type,
        amount: amount,
        description: $('#int_desc').val()
      }, function (resp) {
        if (resp.success) {
          toastr.success(resp.message);
          $('#internalModal').modal('hide');
          setTimeout(function () { location.reload(); }, 800);
        } else {
          toastr.error(resp.message || 'Failed');
          $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Map Internal');
        }
      }, 'json');
    });

    // ============================================================
    // IGNORE / RESTORE
    // ============================================================
    $(document).on('click', '.btn-ignore', function () {
      var txnId = $(this).data('txn-id') || $(this).data('id');
      $('#ignore_transaction_id').val(txnId);
      $('#ignore_reason').val('');
      $('#ignoreModal').modal('show');
    });

    $('#confirmIgnore').click(function () {
      if (!config.ignore_transaction_url) return;
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.post(config.ignore_transaction_url, {
        transaction_id: $('#ignore_transaction_id').val(),
        reason: $('#ignore_reason').val()
      }, function (resp) {
        if (resp.success) {
          toastr.success(resp.message);
          $('#ignoreModal').modal('hide');
          setTimeout(function () { location.reload(); }, 800);
        } else {
          toastr.error(resp.message || 'Failed');
          $btn.prop('disabled', false).html('<i class="fas fa-ban"></i> Ignore');
        }
      }, 'json');
    });

    $(document).on('click', '.btn-restore', function () {
      var txnId = $(this).data('txn-id') || $(this).data('id');
      if (!config.restore_transaction_url) return;
      if (!confirm('Restore this transaction to unmapped status?')) return;

      $.post(config.restore_transaction_url, { transaction_id: txnId }, function (resp) {
        if (resp.success) {
          toastr.success(resp.message);
          setTimeout(function () { location.reload(); }, 800);
        } else {
          toastr.error(resp.message || 'Failed');
        }
      }, 'json');
    });

  });
})(window, jQuery);
