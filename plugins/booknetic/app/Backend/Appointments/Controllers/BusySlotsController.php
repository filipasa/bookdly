<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Backend\Appointments\Services\BusySlotsDataTableService;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;

class BusySlotsController extends Controller
{
    private BusySlotsDataTableService $tableService;

    public function __construct(BusySlotsDataTableService $tableService)
    {
        $this->tableService = $tableService;
    }

    /**
     * @throws CapabilitiesException
     */
    public function index(): void
    {
        $table = $this->tableService->getTable();
        $table = $table->renderHTML();

        $this->view('busy_slots/index', [ 'table' => $table ]);
    }
}
