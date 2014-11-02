<?php

class e20rMeasurementViews {

    private $count;
    private $items;
    private $when;

    private $data;
    private $fields;

    private $unitInfo;

    public function e20rMeasurementViews( $when, $data, $fields ) {

        $this->when = $when;
        $this->data = $data;
        $this->fields = $fields;

    }

    public function startProgressForm() {

        ob_start();
        ?>
        <div id="saturday-progress-container" class="progress-container">
            <h3>Your Weekly Progress
                <span>Update</span>
            </h3>
            <div id="e20r-progress-canvas" style="min-height: 100px;">
                <table class="e20r-progress-form">
                    <tfoot>
                    <tr>
                        <td></td>
                        <td>
                            <button class="submit" id="submit-e20r-tracker-button">
                                <div>Submit Your Weekly Progress Update</div>
                            </button>
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
        <?php
        return ob_get_clean();
    }

    public function endProgressForm() {

        ob_start();
        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }

    public function generateMeasurementHelp( $count, $key ) {

        ob_start();
        ?>
        <tr>
        <?php echo ( ! is_null( $count ) ? '<td class="e20r-number-col">' . $count . '</td>' : "&nbsp;" ); ?>
        <td class="content e20r-measurement-info" id="measured-<?php echo strtolower($key); ?>">
        <fieldset>
        <legend>
            <?php echo $key . " Measurement"; ?><a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">
                <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
            </a>
        </legend>
        <div class="help" style="margin-bottom: 24px;">
            Need some help with taking your <?php echo strtolower($key) ?> measurements? Check out the
            <a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">instructions</a>
            or download the comprehensive
            <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
            in PDF format.
        </div>
        <?php
        return ob_get_clean();
    }

    public function generateMeasurementEnd() {

        ob_start();
        ?>
        </fieldset>
        </td>
        </tr>
        <?php

        return ob_get_clean();
    }


    public function createPhotoBlock( $key, $count = null ) {
        dbg("In createPhotoBlock()");
        ob_start();
        ?>
        <tr>
            <?php echo ( ! is_null( $count ) ? '<td class="e20r-number-col">' . $count . '</td>' : '' ); ?>
            <td class="content e20r-measurement-info" id="<?php echo strtolower($key); ?>">
            <fieldset>
            <legend>
                Progress Photos <a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">
                    <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
                </a>
            </legend>
            <div class="help" style="margin-bottom: 24px;">
                Need some help figuring out how to take your progress photos? Check out the
                <a href="e20r-tracker-help.php?topic=photo" class="lbp_secondary">instructions</a>
                or download the comprehensive
                <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                in PDF format.
            </div>
            <div></div>
        <?php
        return ob_get_clean();

    }

    public function createBlockTable( $list, $key, $count ) {

        // TODO: Fix the data for this table (it's assumed to be available.)

        dbg("In createBlockTable()");
        dbg("Content of list: " . print_r( $list, true));

        dbg( "{$key} measurements on {$this->when}" );

        ob_start();

        foreach ( $list as $item => $value ) {
            ?>
            <h5 class="measurement-header">
                <span class="title"><?php echo ucfirst($item) . " " . ucfirst($key); ?></span>
                <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
            </h5>
            <div class="<?php echo strtolower($key); ?>-row-container">
                <div class="measurement-image" style="<?php echo $this->bgImage( strtolower($item), strtolower($key) ); ?>"></div>
                <div class="measurement-descr-container">
                    <p style="margin-bottom: 52px;">
                        <?php echo $this->measurementDescr( $item ); ?>
                    </p>
                    <div class="measurement-field-container">
                        <div class="label-container">
                            <label>Enter <?php echo ucfirst($item) . ' ' . ucfirst($key); ?></label>
                        </div>
                            <input type="text" class="measurement-input" value="<?php echo ( ! empty( $this->data->{$this->fields[strtolower($item)]} ) ? $this->data->{$this->fields[strtolower($item)]} : '' ); ?>" data-measurement-type="<?php echo strtolower($key) .'_'. strtolower($item); ?>" style="width: 70px; font-size: 20px; text-align: center;">
                        <span class="unit length"><?php echo $this->unit_type->distance; ?></span>
                    </div>
                    <div class="measurement-saved-container">
                        <div class="label-container">
                            <label>Entered <?php echo ucfirst($item) . ' ' . ucfirst($key); ?></label>
                        </div>
                        <span class="value"><?php echo ( ! empty( $this->data->{$this->fields[strtolower($item)]} ) ? $this->data->{$this->fields[strtolower($item)]} : '' ); ?></span>
                        <span class="unit length"><?php echo $this->unit_type->distance; ?></span>
                        <button class="edit">Change <?php echo $key; ?></button>
                    </div>
                </div>
            </div>
        <?php
        }

        return ob_get_clean();
    }

    public function createInputBlock( $item, $count = null, $date ) {

        ?>
        <!-- TODO: Add option to change measurement Unit, if wanted when updating body weight -->
        <div class="measurement-field-container">
            <div class="e20r-label">
                <label>Enter Current <?php echo $item; ?></label>
            </div>
            <input type="text" value="<?php echo ( ! empty( $this->data->{$this->fields[strtolower($item)]} ) ? $this->data->{$this->fields[strtolower($item)]} : '' ); ?>" class="e20r-measurement-input" data-measurement-type="<?php echo strtolower($item); ?>" style="width: 70px; font-size: 20px; text-align: center;">
            <span class="unit <?php echo strtolower($item); ?>"> </span>
        </div>
        <div class="measurement-saved-container">
            <div class="e20r-label">
                <label>Entered <?php echo $item; ?></label>
            </div>
            <span class="value"><?php echo (! empty( $this->data->{$this->fields[strtolower($item)]} ) ? $this->data->{$this->fields[strtolower($item)]} : null ); ?></span>
            <span class="unit <?php echo strtolower($item); ?>"><?php echo $this->unit_type->mass; ?></span>
            <button class="edit">Change <?php echo $item; ?></button>
        </div>
    <?php
    }
} 