$(document).ready(function() {
    // Sidebar toggle for mobile
    $('#sidebarToggleBtn, #tool_hide').click(function() {
        $('#sidebar').toggleClass('show');
    });
    
    // Close sidebar button functionality
    $('#closeBtn').click(function() {
        $('#sidebar').toggleClass('show');
        if ($(window).width() > 768) {
            $('#sidebar').toggleClass('collapsed');
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', $('#sidebar').hasClass('collapsed'));
        }
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#sidebar').length && 
                !$(e.target).closest('#sidebarToggleBtn').length &&
                !$(e.target).closest('#tool_hide').length) {
                $('#sidebar').removeClass('show');
            }
        }
    });
    
    // Load collapsed state from localStorage
    let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && $(window).width() > 768) {
        $('#sidebar').addClass('collapsed');
    }
    
    // Add tooltips for collapsed sidebar
    $('.menu-link, .submenu-link, .logout-link').each(function() {
        let text = $(this).find('.menu-text, span:last').text().trim();
        if (text && text !== 'Logout') {
            $(this).attr('data-tooltip', text);
        }
    });
    
    // Active menu item highlighting and parent section expansion
    let currentUrl = window.location.pathname.split('/').pop();
    $('.menu-link, .submenu-link').each(function() {
        let href = $(this).attr('href');
        if (href === currentUrl) {
            $(this).addClass('active');
            // Expand parent section
            $(this).closest('.submenu').prev('.section-title').addClass('active');
            // Scroll to active item
            setTimeout(function() {
                $('.active')[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    });
    
    // Handle window resize
    $(window).resize(function() {
        if ($(window).width() > 768) {
            $('#sidebar').removeClass('show');
        }
    });
});