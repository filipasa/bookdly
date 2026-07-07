<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
?>

<?php if (count($parameters[ 'locations' ]) == 0): ?>
    <div class="booknetic_empty_box">
        <img alt="" src="<?php echo Helper::assets('images/empty-service.svg', 'front-end') ?>">
        <span>
            <?php echo bkntc__('There is no any Location for select.') ?>
        </span>
    </div>
<?php else: ?>
    <div class="booknetic_card_container">
        <?php
        $lastCategoryPrinted = null;
    $isAccordionEnabled = $parameters['hide_location_accordion_default'] ?? 'off';
    $showCategories = false;

    if ($isAccordionEnabled === 'on') {
        foreach ($parameters['locations'] as $location) {
            if (!empty($location['category_id'])) {
                $showCategories = true;
                break;
            }
        }
    }

    foreach ($parameters[ 'locations' ] as $eq => $location):
        $categoryId = $location['category_id'] ?? 0;
        $categoryName = !empty($location['location_categories_name']) ? $location['location_categories_name'] : ($categoryId > 0 ? '' : bkntc__('Uncategorized'));

        if ($showCategories && $lastCategoryPrinted != $categoryId):
            if ($lastCategoryPrinted !== null):
                echo '</div>';
            endif;

            echo '<div class="booknetic_category_accordion" data-accordion="on">';

            echo '<div data-parent="1" class="booknetic_location_category booknetic_fade">' . htmlspecialchars($categoryName) . '<span data-parent="1"></span></div>';
            $lastCategoryPrinted = $categoryId;
        endif;
        ?>
            <div class="booknetic_card booknetic_fade" data-id="<?php echo $location[ 'id' ] ?>">
                <div class="booknetic_card_image">
                    <img class="booknetic_card_location_image"
                         src="<?php echo Helper::profileImage($location[ 'image' ], 'Locations') ?>">
                </div>
                <div class="booknetic_card_title">
                    <div class="booknetic_card_title_first"><?php echo htmlspecialchars($location[ 'name' ]) ?></div>
                    <div class="booknetic_card_description<?php echo Helper::getOption('hide_address_of_location', 'off') == 'on' ? ' booknetic_hidden' : '' ?>"><?php echo htmlspecialchars($location[ 'address' ]) ?></div>
                </div>
            </div>
        <?php endforeach;

if ($showCategories && $lastCategoryPrinted !== null):
    echo '</div>';
endif;
?>
    </div>
<?php endif; ?>
