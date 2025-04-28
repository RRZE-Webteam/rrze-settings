import "./replace.scss";

jQuery(document).ready(function ($) {
    $(".rrze-media-upload").on("change", function () {
        var fileValue =
            $(this).val() !== ""
                ? "..." + $(this).val().substr(-15)
                : rrzeMediaReplace.nothing_selected;
        $(".rrze-media-upload-value").html("").append(fileValue);
    });
});
