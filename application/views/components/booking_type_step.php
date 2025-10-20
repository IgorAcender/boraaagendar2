<?php
/**
 * Local variables.
 *
 * @var array $available_services
 */
?>

<div id="wizard-frame-1" class="wizard-frame" style="visibility: hidden;">
    <div class="frame-container">
        <h2 class="frame-title mt-md-5"><?= lang('service_and_provider') ?></h2>

        <div class="row frame-content">
            <div class="col col-md-8 offset-md-2">
                <!-- Select Provider first (inverted flow) -->
                <div class="mb-3">
                    <label for="select-provider">
                        <strong><?= lang('provider') ?></strong>
                    </label>

                    <select id="select-provider" class="form-select">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                        <?php
                        $available_providers = vars('available_providers');
                        foreach ($available_providers as $provider) {
                            echo '<option value="' . $provider['id'] . '">' .
                                e($provider['first_name'] . ' ' . $provider['last_name']) .
                                '</option>';
                        }
                        // Add "Any Provider" entry if setting is enabled and more than one provider exists.
                        if (vars('display_any_provider') && count($available_providers) > 1) {
                            echo '<option value="any-provider">' . lang('any_provider') . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <?php slot('after_select_provider'); ?>

                <!-- Now select Service (filtered by provider) -->
                <div class="mb-3" hidden>
                    <label for="select-service">
                        <strong><?= lang('service') ?></strong>
                    </label>

                    <select id="select-service" class="form-select">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                    </select>
                </div>

                <?php slot('after_select_service'); ?>

                <div id="service-description" class="small">
                    <!-- JS -->
                </div>

                <?php slot('after_service_description'); ?>

            </div>
        </div>
    </div>

    <div class="command-buttons">
        <span>&nbsp;</span>

        <button type="button" id="button-next-1" class="btn button-next btn-dark"
                data-step_index="1">
            <?= lang('next') ?>
            <i class="fas fa-chevron-right ms-2"></i>
        </button>
    </div>
</div>
