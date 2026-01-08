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
        case 'emi': return 'primary';
        case 'savings': return 'success';
        case 'fine': return 'danger';
        case 'expense': return 'warning';
        default: return 'secondary';
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

  });
})(window, jQuery);
