<?php

namespace BookneticApp\Backend\Workflow\Actions;

use BookneticApp\Config;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Common\WorkflowDriver;

class SetCustomerCategory extends WorkflowDriver
{
    protected $driver = 'set_customer_category';

    public function __construct()
    {
        $this->setName(bkntc__('Set customer category'));
        $this->setEditAction('workflow_actions', 'set_customer_category_view');
    }

    public function handle($eventData, $actionSettings, $shortCodeService)
    {
        if (empty($eventData['customer_id'])) {
            throw new \RuntimeException(bkntc__('Customer ID is missing.'));
        }

        $actionData = json_decode($actionSettings['data'], true);

        if (empty($actionData)) {
            throw new \RuntimeException(bkntc__('Set customer category action data is empty.'));
        }

        $customerCategory = CustomerCategory::query()->where('id', $actionData['category_id'])->fetch();

        if ($customerCategory === null) {
            throw new \RuntimeException(bkntc__('Customer category not found.'));
        }

        $customer = Customer::query()->where('id', $eventData['customer_id'])->fetch();

        if ($customer === null) {
            throw new \RuntimeException(bkntc__('Customer not found.'));
        }

        Customer::query()->where('id', $eventData['customer_id'])->update(['category_id' => $actionData['category_id']]);

        Config::getWorkflowEventsManager()->setEnabled(Config::getWorkflowEventsManager()->isEnabled());
    }
}
