jQuery(document).ready(function($) {
    console.log("jQuery is loaded");
    console.log("Initializing datepicker");
    $("input[type=date]").each(function() {
        console.log("Found date input:", this);
        console.log("Initializing jQuery UI Datepicker for:", this);
        $(this).datepicker({
            dateFormat: "yy-mm-dd"
        });
    });
});