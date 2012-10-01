$(document).ready(function () {

    $("form.dotag select").change(function () {

        // get place
        var place = $(this).parent().find(".place").val();

        var $this = $(this);
        var label = $(this).find("option:selected").text();
        var tr = $(this).parents("tr");

        // send the request
        $.post('/tags/tag', { category_id: $(this).val(), place_id: place }, function(response) {
            $this.replaceWith('<span class="label">' + label  + '</span>');
            setTimeout(function () {
                tr.hide("slow", function () { tr.remove() });
            }, 2000);
        });

    });

});