<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Workflow\DTOs\Response\WorkflowLogDetailsResponse;

/**
 * @var WorkflowLogDetailsResponse $parameters
 */
?>

<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fa fa-info-circle"></i></div>
	<div class="title-text"><?php echo bkntc__('Log details')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">

		<div class="form-row">
			<div class="form-group col-md-6">
				<label class="font-semibold"><?php echo bkntc__('Workflow')?></label>
				<div class="form-control-plaintext"><?php echo htmlspecialchars($parameters->getWorkflowName())?></div>
			</div>
			<div class="form-group col-md-6">
				<label class="font-semibold"><?php echo bkntc__('Date')?></label>
				<div class="form-control-plaintext"><?php echo htmlspecialchars($parameters->getDateTime())?></div>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-6">
				<label class="font-semibold"><?php echo bkntc__('Event')?></label>
				<div class="form-control-plaintext"><?php echo htmlspecialchars($parameters->getEventTitle())?></div>
			</div>
			<div class="form-group col-md-6">
				<label class="font-semibold"><?php echo bkntc__('Action')?></label>
				<div class="form-control-plaintext"><?php echo htmlspecialchars($parameters->getDriverName())?></div>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-12">
				<label class="font-semibold"><?php echo bkntc__('Status')?></label>
				<div class="form-control-plaintext">
					<?php if ($parameters->isError()): ?>
						<span class="btn btn-xs btn-light-danger" style="cursor: initial"><?php echo bkntc__('Failed')?></span>
					<?php else: ?>
						<span class="btn btn-xs btn-light-success" style="cursor: initial"><?php echo bkntc__('Success')?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ($parameters->isError() && !empty($parameters->getErrorMessage())): ?>
		<div class="form-row">
			<div class="form-group col-md-12">
				<label class="font-semibold"><?php echo bkntc__('Error message')?></label>
				<div class="form-control-plaintext text-danger"><?php echo htmlspecialchars($parameters->getErrorMessage())?></div>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($parameters->getEventData())): ?>
		<div class="form-row">
			<div class="form-group col-md-12">
				<label class="font-semibold"><?php echo bkntc__('Event data')?></label>
				<div class="table-responsive">
					<table class="table table-bordered table-sm">
						<tbody>
						<?php foreach ($parameters->getEventData() as $key => $value): ?>
							<tr>
								<td style="width: 30%"><?php echo htmlspecialchars($key)?></td>
								<td><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : (string)$value)?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($parameters->getActionData())): ?>
		<div class="form-row">
			<div class="form-group col-md-12">
				<label class="font-semibold"><?php echo bkntc__('Action data')?></label>
				<div class="table-responsive">
					<table class="table table-bordered table-sm">
						<tbody>
						<?php foreach ($parameters->getActionData() as $key => $value): ?>
							<tr>
								<td style="width: 30%"><?php echo htmlspecialchars($key)?></td>
								<td><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : (string)$value)?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php endif; ?>

	</div>
</div>

<div class="fs-modal-footer">
	<?php if ($parameters->canRetry()): ?>
		<button type="button" class="btn btn-lg btn-primary" id="detailsRetryBtn" data-log-id="<?php echo $parameters->getId()?>"><?php echo bkntc__('RETRY')?></button>
	<?php endif; ?>
	<button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
</div>
