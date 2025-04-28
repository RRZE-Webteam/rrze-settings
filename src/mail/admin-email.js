jQuery(document).ready(function ($) {
    $("#new_admin_email")
        .attr("disabled", "disabled")
        .siblings("p.description")
        .hide();
});
