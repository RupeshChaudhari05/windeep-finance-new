/**
 * Font Size Adjuster
 * Session-only font size control for admin panel
 * Uses localStorage (session scoped)
 */

$(document).ready(function () {
  const MIN_FONT_SIZE = 85;  // 85% minimum
  const MAX_FONT_SIZE = 130; // 130% maximum
  const STEP = 5;            // 5% step increment
  const DEFAULT_SIZE = 100;
  const STORAGE_KEY = 'admin_font_size_session';

  // Initialize font size from localStorage
  function initFontSize() {
    let savedSize = localStorage.getItem(STORAGE_KEY);
    if (!savedSize) {
      savedSize = DEFAULT_SIZE;
      localStorage.setItem(STORAGE_KEY, DEFAULT_SIZE);
    }
    applyFontSize(parseInt(savedSize));
  }

  // Apply font size to entire document
  function applyFontSize(size) {
    size = Math.max(MIN_FONT_SIZE, Math.min(MAX_FONT_SIZE, size));
    document.documentElement.style.fontSize = size + '%';
    localStorage.setItem(STORAGE_KEY, size);
    updateButtonDisplay(size);

    // Save to session storage as well for redundancy
    sessionStorage.setItem(STORAGE_KEY, size);
  }

  // Update button display with current size
  function updateButtonDisplay(size) {
    $('#fontSize-current').html('<small>' + size + '%</small>');

    // Update button states
    $('#fontSize-decrease').prop('disabled', size <= MIN_FONT_SIZE);
    $('#fontSize-increase').prop('disabled', size >= MAX_FONT_SIZE);

    // Visual feedback
    if (size > DEFAULT_SIZE) {
      $('#fontSize-current').addClass('btn-primary').removeClass('btn-light');
    } else if (size < DEFAULT_SIZE) {
      $('#fontSize-current').addClass('btn-warning').removeClass('btn-light btn-primary');
    } else {
      $('#fontSize-current').addClass('btn-light').removeClass('btn-primary btn-warning');
    }
  }

  // Decrease font size
  $('#fontSize-decrease').on('click', function () {
    let current = parseInt(localStorage.getItem(STORAGE_KEY) || DEFAULT_SIZE);
    let newSize = Math.max(MIN_FONT_SIZE, current - STEP);
    applyFontSize(newSize);
  });

  // Increase font size
  $('#fontSize-increase').on('click', function () {
    let current = parseInt(localStorage.getItem(STORAGE_KEY) || DEFAULT_SIZE);
    let newSize = Math.min(MAX_FONT_SIZE, current + STEP);
    applyFontSize(newSize);
  });

  // Reset to default
  $('#fontSize-reset').on('click', function () {
    localStorage.setItem(STORAGE_KEY, DEFAULT_SIZE);
    sessionStorage.setItem(STORAGE_KEY, DEFAULT_SIZE);
    applyFontSize(DEFAULT_SIZE);

    // Show brief feedback
    $(this).text('Reset ✓').prop('disabled', true);
    setTimeout(function () {
      $('#fontSize-reset').text('Reset').prop('disabled', false);
    }, 1500);
  });

  // Initialize on page load
  initFontSize();
});
