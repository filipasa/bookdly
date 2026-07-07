(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#connected_tenants_modal').on( 'click', '.delete_btn', function()
        {
            let checkboxes = $('.connected_tenants_checkbox:checked')

            if ( checkboxes.length <= 0 )
            {
                booknetic.toast( 'Please select a tenant before deleting', 'unsuccess' )
            }
            else
            {
                let tenantAccounts = checkboxes.map( ( index, el ) => {
                    return {
                        'id' : $(el).closest('li').attr('data-tenant-id'),
                        'account' : $(el).closest('li').attr('data-account-id')
                    }
                } ).toArray()

                booknetic.ajax( 'stripe_connect_settings.delete_connected_tenant_account', { 'accounts' : JSON.stringify(tenantAccounts) }, function (res) {
                    booknetic.toast( res.message )
                    location.reload()
                } )

            }
        })

    });

})(jQuery);
