jQuery(document).ready(function ($) {
    // Hide the avatar settings section
    var $table = $("#show_avatars").closest("table");
    var $p = $table.prev("p");
    var $h2 = $p.prev("h2");
    $h2.add($p).add($table).hide();
});
