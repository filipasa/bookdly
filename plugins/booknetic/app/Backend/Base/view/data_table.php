<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

if (!$dataTable['is_ajax']) {
    ?>

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo $dataTable['title'] ?> <span class="badge badge-warning row_count"><?php echo $dataTable['row_count'] ?></span></div>
	<div class="m_head_actions float-right">

		<?php
            if ($dataTable['export_btn']) {
                echo '<button type="button" class="btn btn-outline-secondary btn-lg export_csv"><i class="fa fa-upload"></i> ' . bkntc__('EXPORT TO CSV') . '</button>';
            }

    if ($dataTable['import_btn']) {
        echo '<button type="button" class="btn btn-outline-secondary btn-lg" id="importBtn"><i class="fa fa-download"></i> ' . bkntc__('IMPORT') . '</button>';
    }

    if (!empty($dataTable['add_new_btn'])) {
        echo '<button type="button" data-setp="1" class="btn btn-primary btn-lg" id="addBtn"><i class="fa fa-plus"></i> ' . $dataTable['add_new_btn'] . '</button>';
    }

    ?>

	</div>
</div>

<?php
if (!$dataTable['hide_search']) {
    $getColMdForSearchPanel = 12;
    foreach ($dataTable['filters'] as $filterId => $filter) {
        $getColMdForSearchPanel -= (int)$filter['col_md'];
    }
    ?>
<div class="data_table_search_panel">
	<div class="row m-0 p-0">
		<div class="col-md-<?php echo $getColMdForSearchPanel?> m-0 p-0">
			<div class="input-icon">
				<i><img src="<?php echo Helper::icon('search.svg')?>"></i>
				<input type="text" class="form-control form-control-lg search_input" placeholder="<?php echo bkntc__('Quick search')?>" value="<?php echo htmlspecialchars($dataTable['search']) ?>">
			</div>
		</div>
		<?php

            foreach ($dataTable['filters'] as $filterId => $filter) {
                ?>
			<div class="col-md-<?php echo $filter['col_md']?> m-0 p-0">
				<?php
                    if ($filter['input_type'] == 'select') {
                        ?>
					<select class="form-control" data-filter-id="<?php echo (int)$filterId?>" data-placeholder="<?php echo htmlspecialchars($filter['placeholder'])?>">

					</select>
					<?php
                    } elseif ($filter['input_type'] == 'date') {
                        ?>
					<div class="position-relative">
						<input type="text" data-type="date" class="form-control" data-date-format="<?php echo htmlspecialchars(Helper::getOption('date_format', 'Y-m-d')) ?>" data-filter-id="<?php echo (int)$filterId?>" placeholder="<?php echo htmlspecialchars($filter['placeholder'])?>">
						<span class="datepicker_clear_btn">×</span>
					</div>
					<?php
                    } elseif ($filter['input_type'] == 'input') {
                        ?>
					<input type="text" class="form-control" data-filter-id="<?php echo (int)$filterId?>" placeholder="<?php echo htmlspecialchars($filter['placeholder'])?>">
					<?php
                    }
                ?>

			</div>
			<?php
            }

    ?>


	</div>

	<hr>
</div>
<?php
}
    ?>
<div class="m_content pt-0" id="fs_data_table_div">
	<?php
}
?>
	<div class="fs_data_table_wrapper table-wrap">
		<div class="m_bottom_fixed bulk-bar hidden" style="display: none;">
			<div class="d-flex align-items-center">
				<input type="checkbox" class="cb mr-3" id="checkbox_select_all_bulk">
				<span><span class="selected_count">0</span> <?php echo bkntc__('selected')?></span>
			</div>
			<div class="bulk-action-btns">
				<?php foreach ($dataTable['actions'] as $key => $action): ?>
					<?php if ($action['flags'] & \BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI::ACTION_FLAG_BULK): ?>
						<button type="button" class="bulk_action_btn <?php echo ($key === 'delete' ? 'del' : '') ?>" data-action="<?php echo htmlspecialchars($key) ?>"><?php echo htmlspecialchars($action['title']) ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
				<select name="" class="bulk_action hidden" style="display:none !important;">
					<?php foreach ($dataTable['actions'] as $key => $action): ?>
					<?php if ($action['flags'] & \BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI::ACTION_FLAG_BULK): ?>
						<option value="<?php echo htmlspecialchars($key) ?>"><?php echo htmlspecialchars($action['title']) ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<button type="button" class="datatable_apply_btn hidden" style="display:none !important;"></button>
			</div>
		</div>
		<table class="fs_data_table elegant_table data-table">
			<thead>
			<tr>
				<th>
					<input type="checkbox" class="select_data_all_checkbox<?php echo $dataTable['bulk_action'] ? '' : ' hidden'?>" id="checkbox_select_all">
				</th>
				<?php

            foreach ($dataTable['thead'] as $columnKey => $column) {
                $addClass = [];
                $addAttr = '';

                if ($column['is_sortable']) {
                    $addClass[] = 'is_sortable';

                    if ($column['order_by_field'] == $dataTable['order_by']) {
                        $addClass[] = 'active_order_field';
                        $addAttr .= ' data-order-type="' . ($dataTable['order_by_type'] == 'ASC' ? 'ASC' : 'DESC') . '"';
                    }
                }

                $addClass = empty($addClass) ? '' : ' class="' . implode(' ', $addClass) . '"';

                echo '<th data-column="' . (int)$columnKey . '"' . $addClass . $addAttr . '>' . htmlspecialchars($column['name']) . '</th>';
            }

?>
				<th class="text-right"><?php echo !empty($dataTable['actions']) ? bkntc__('Actions') : '' ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
            foreach ($dataTable['tbody'] as $rows) {
                $rid = $rows['id'];
                $attributes = '';
                foreach ($dataTable['attributes'] as $dataName => $dataValues) {
                    $attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
                }

                ?>
				<tr data-id="<?php echo $rid?>"<?php echo $rows['attributes'] . (!$rows['is_active'] ? ' data-disabled="true"' : '')?>>
					<td><input type="checkbox" id="checkbox_select_all<?php echo $rid?>" class="select_data_checkbox <?php echo $dataTable['bulk_action'] ? '' : ' hidden'?>"></td>

					<?php
                    foreach ($rows['data'] as $data) {
                        $attributes = '';
                        foreach ($data['attributes'] as $dataName => $dataValues) {
                            $attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
                        }

                        echo '<td'.$attributes.'>' . $data['content'] . '</td>';
                    }
                ?>

                    <td class="text-right">
                        <?php if (!empty($dataTable['actions'])):?>
                                <i class="fa fa-eye-slash row_is_disabled"></i>
                                <span class="actions_btn" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false"><i class="fa fa-ellipsis-h"></i></span>
                                <div class="dropdown-menu dropdown-menu-right row-actions-area">
                                    <?php foreach ($dataTable['actions'] as $key => $action):?>
                                        <?php if ($action['flags'] & \BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI::ACTION_FLAG_SINGLE): ?>
                                            <button class="dropdown-item datatable_action_btn" data-action="<?php echo $key ?>" type="button"><?php echo $action['title']?></button>
                                        <?php endif; ?>
                                    <?php endforeach;?>
                                </div>
                        <?php endif; ?>
                    </td>
				</tr>
				<?php
            }
if (empty($dataTable['tbody'])) {
    echo '<tr><td colspan="100%" class="pl-4 text-secondary">' . bkntc__('No entries!') . '</td></tr>';
}
?>
			</tbody>
		</table>

		<div class="pagination">
			<span class="pagination-info"><?php echo bkntc__('Showing %d of %d total', [ count($dataTable['tbody']) , $dataTable['row_count'] ])?></span>
			<div class="page-btns">
				<button type="button" class="pg-btn prev_page" <?php echo ($dataTable['current_page'] <= 1 ? 'disabled' : '') ?>>‹</button>
				<?php

    if ($dataTable['pagination'] !== false) {
        if ($dataTable['max_page'] <= 7) {
            $startPage = 2;
            $endPage = $dataTable['max_page'] - 1;
        } else {
            $startPage = $dataTable['current_page'] - 2;
            $endPage = $startPage + 4;

            if ($startPage < 2) {
                $endPage += 2 - $startPage;
                $startPage = 2;
            }

            if ($endPage > $dataTable['max_page'] - 1) {
                $startPage -= 1 - ($dataTable['max_page'] - $endPage);
                $endPage = $dataTable['max_page'] - 1;
            }
        }

        echo '<span class="page_class badge' . (1 == $dataTable['current_page'] ? ' active_page badge-default' : '') . '">1</span>' . ($startPage > 2 ? ' <span class="page-ellipsis">…</span> ' : '');

        for ($page = $startPage; $page <= $endPage; $page++) {
            echo '<span class="page_class badge' . ($page == $dataTable['current_page'] ? ' active_page badge-default' : '') . '">' . $page . '</span>';
        }

        if ($dataTable['max_page'] >= 2) {
            echo ($dataTable['max_page'] - 1 > $endPage ? ' <span class="page-ellipsis">…</span> ' : '') . '<span class="page_class badge' . ($dataTable['max_page'] == $dataTable['current_page'] ? ' active_page badge-default' : '') . '">' . $dataTable['max_page'] . '</span>';
        }
    }

?>
				<button type="button" class="pg-btn next_page" <?php echo ($dataTable['current_page'] >= $dataTable['max_page'] ? 'disabled' : '') ?>>›</button>
			</div>
		</div>
	</div>
	<?php
    if (!$dataTable['is_ajax']) {
        ?>
</div>

<script>
    $(document).off('click', '.m_bottom_fixed .bulk_action_btn').on('click', '.m_bottom_fixed .bulk_action_btn', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        var wrapper = $(this).closest('.m_bottom_fixed');
        wrapper.find('.bulk_action').val(action);
        wrapper.find('.datatable_apply_btn').trigger('click');
    });
    $(document).off('change', '#checkbox_select_all_bulk').on('change', '#checkbox_select_all_bulk', function() {
        $(this).closest('.fs_data_table_wrapper').find('#checkbox_select_all').prop('checked', $(this).is(':checked')).trigger('change');
    });
</script>
<?php
    }
?>