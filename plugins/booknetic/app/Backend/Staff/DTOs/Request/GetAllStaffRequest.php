<?php

namespace BookneticApp\Backend\Staff\DTOs\Request;

class GetAllStaffRequest
{
    public string $search = '';
    public int $location = 0;
    public int $service = 0;
}
