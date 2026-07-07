(function ($)
{
    "use strict";

    $(document).ready(function()
    {

        $(document).on('click', '#addBtn', function ()
        {
            booknetic.loadModal('add_new', {});
        });

        booknetic.dataTable.actionCallbacks['edit'] = function (ids)
        {
            booknetic.loadModal('add_new', {'id': ids[0]});
        }

    });

})(jQuery);
