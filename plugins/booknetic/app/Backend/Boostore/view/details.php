<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Boostore\DTOs\Response\AddonDetailResponse;
use BookneticApp\Backend\Boostore\DTOs\Response\SubscriptionResponse;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * @var AddonDetailResponse $addon
 * @var SubscriptionResponse|null $subscription
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/shared.css', 'Boostore') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/details.css', 'Boostore') ?>" type="text/css">

<?php if (! empty($parameters['addon'])) {
    $addon = $parameters['addon'];
    $subscription = $parameters['subscription'];
    $currentPlan = !empty($subscription) ? $subscription->getPlanSlug() : '';
    $isIncludedInPlan = $addon->isIncludedInPlan($currentPlan);
    ?>
    <div class="boostore">
        <!-- Page header -->
        <div class="m_header clearfix">
            <div class="m_head_title float-left">
                <a href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore"><?php echo bkntc__('Add-ons'); ?></a>
                <i class="mx-2"><img src="<?php echo Helper::icon('arrow.svg'); ?>" alt=""></i>
                <span class="name"><?php echo $addon->getName(); ?></span>
            </div>
        </div>

        <!-- Addon info -->
        <section class="row details_info">
            <div class="col-lg-7 col_content order-lg-1 order-2 d-flex flex-column justify-content-between">
                <div>
                    <span class="info_category mb-3">
                        <?php echo htmlspecialchars($addon->getCategory()->getName()); ?>
                    </span>

                    <h1 class="mb-3"><?php echo htmlspecialchars($addon->getName()); ?></h1>

                    <?php if ($addon->isReleased() && empty($subscription)): ?>
                        <div class="info_price boostore_price d-flex align-items-center mt-1">
                            <?php if ($addon->isOwned()): ?>
                            <?php elseif ($addon->getPrice()->isFree()): ?>
                                <span class="free"><?php echo bkntc__('Free') ?></span>
                            <?php else: ?>
                                <?php if ($addon->getPrice()->hasDiscount()): ?>
                                    <span class="discount"><?php echo '$' . Math::floor($addon->getPrice()->getOld(), 1) ?></span>
                                <?php endif ?>

                                <span><?php echo '$' . Math::floor($addon->getPrice()->getCurrent(), 1) ?></span>
                                <p class="mb-0 ml-2">One time payment</p>
                            <?php endif ?>
                        </div>
                    <?php endif ?>

                    <div class="download mt-3"><i class="fa fa-arrow-circle-down"></i> <?php echo $addon->getDownloads(); ?></div>
                </div>
                <div class="">
                    <?php if (!empty($currentPlan) && ! $isIncludedInPlan): ?>
                        <button class="btn btn-warning btn-lg mr-2 mb-2 btn-upgrade" data-addon="<?php echo htmlspecialchars($addon->getSlug()); ?>">
                            <i class="fa fa-crown mr-2"></i>
                            <?php echo bkntc__('UPGRADE'); ?>
                        </button>
                    <?php elseif ($addon->isInstalled()): ?>
                        <button class="btn btn-outline-danger btn-lg mt-4 btn-uninstall" data-addon="<?php echo htmlspecialchars($addon->getSlug()); ?>">
                            <?php echo bkntc__('UNINSTALL'); ?>
                        </button>
                    <?php elseif ($addon->isOwned() || $isIncludedInPlan): ?>
                        <button class="btn btn-success btn-lg mt-4 btn-install" data-addon="<?php echo htmlspecialchars($addon->getSlug()); ?>">
                            <?php echo bkntc__('INSTALL'); ?>
                        </button>
                    <?php elseif (! $addon->isReleased()): ?>
                        <button class="btn btn-light-warning btn-lg mr-2 mb-2">
                            <?php echo bkntc__('SOON'); ?>
                        </button>
                    <?php elseif ($addon->hasErrorMessage()): ?>
                        <div class="text-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($addon->getErrorMessage()); ?>
                        </div>
                    <?php elseif ($addon->isInCart()): ?>
                        <a class="btn btn-lg btn-warning view_cart_btn mb-2 mr-2" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=cart"> <i class="fa fa-shopping-cart mr-2" aria-hidden="true"></i> <?php echo bkntc__('VIEW CART'); ?> </a>
                    <?php elseif ($addon->isUnowned()): ?>
                        <button class="btn btn-primary btn-lg mr-2 mb-2 btn-add-to-cart" data-addon="<?php echo htmlspecialchars($addon->getSlug()); ?>">
                            <?php echo bkntc__('ADD TO CART'); ?>
                        </button>
                    <?php elseif ($addon->isPending()): ?>
                        <button class="btn btn-light-warning btn-lg mt-4">
                            <?php echo bkntc__('PENDING...'); ?>
                        </button>
                    <?php endif; ?>
                </div>

            </div>

            <div class="col-lg-5 d-flex align-items-center col_img order-lg-2 order-1 mb-2">
                <img src="<?php echo $addon->getCover() ?>" alt="<?php echo htmlspecialchars($addon->getName()) ?>">
            </div>
        </section>

        <hr />

        <section class="details_content">
            <div>
                <section id="tab_details" class="tab-pane active">
                    <div class="row">
                        <!-- Content -->
                        <div class="col-lg-8 col_content order-lg-1 order-2">
                            <div class="title mb-3">General review</div>
                            <?php echo $addon->getDescription(); ?>
                        </div>

                        <!-- Info -->
                        <div class="col-lg-4 col_info order-lg-2 order-1 mb-lg-0 mb-5">
                            <div>
                                <?php if ($addon->hasLatestVersion()): ?>
                                    <div class="info_item">
                                        <b><?php echo bkntc__('Latest version'); ?>:</b>
                                        <span><?php echo htmlspecialchars($addon->getLatestVersion()->getVersionString()); ?></span>
                                    </div>

                                    <?php if (! $addon->isLatestVersionCompatible()): ?>
                                        <div class="info_not-compatible text-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo bkntc__('Latest version %s requires minimum Booknetic %s.', [
                                                    $addon->getLatestVersion()->getVersionString(),
                                                    $addon->getLatestVersion()->getRequiredBookneticVersionString()
                                                ]); ?>
                                        </div>

                                        <div class="info_item">
                                            <b><?php echo bkntc__('Compatible version'); ?>:</b>
                                            <span><?php echo htmlspecialchars($addon->getLatestCompatibleVersion()->getVersionString()); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="info_not-compatible text-success">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo bkntc__('Latest version is compatible with your Booknetic version.'); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php foreach ($addon->getInfo() as $k => $v): ?>
                                    <div class="info_item"><b><?php echo htmlspecialchars($k); ?>:</b>
                                        <span><?php echo htmlspecialchars($v); ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
<?php } ?>

<script src="<?php echo Helper::assets('js/shared.js', 'Boostore'); ?>"></script>
<script src="<?php echo Helper::assets('js/details.js', 'Boostore'); ?>"></script>
