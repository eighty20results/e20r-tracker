<?php
namespace E20R\Tracker\Views;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */


class Settings_View {

    protected $cpt_slug;
    protected $type;
    protected $error;

    private static $instance = null;

    protected function __construct( $type = null, $cpt_slug = null ) {

        $this->type = $type;
        $this->cpt_slug = $cpt_slug;

    }

	/**
	 * @return  Settings_View
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    protected function setError( $text ) {

            $this->error = $text;
    }

    protected function displayError() {

        if ( !empty( $this->error ) ) {

            ?><div class="message error"><p><?php echo( $this->error ); ?></p></div><?php
        }
    }
    /**
     * Generate an input table cell
     * @param $type - type of input ('date', 'number', 'text', etc.
     * @param $name - variable name for the setting
     * @param $data - Data value for the setting.
     *
     * @visibility protected
     */
    protected function buildInput( $type, $name, $data, $style = null ) {
        ?>
        <td class="text-input">
            <input <?php echo ( !is_null( $style ) ? sprintf( 'style="%s"', $style ) : null ); ?> type="<?php esc_attr_e( $type ); ?>" id="e20r-setting-input-<?php esc_attr_e( $name ); ?>" name="e20r-setting-input-<?php esc_attr_e(  $name ); ?>" value="<?php esc_attr_e( $data ); ?>">
        </td>
        <?php
    }

    /**
     * Generate a select2 drop-down table cell for the table
     * @param $name -- Name of the field/setting vaiable
     * @param $data -- Values to list as options (Format: array( $key => $name )
     * @param $comparison -- Array of comparison values for the options. ( Format: array( $list )
     *
     * @visibility protected
     */
    protected function buildSelect2( $name, $data, $comparison, $style = null ) {
        ?>
        <td class="select-input">
            <select class="select2-container" id="e20r-setting-select-<?php echo $name; ?>" <?php echo ( stripos( $name, '[]') ? 'multiple="multiple"' : null ); ?>></select>
            <?php
            foreach( $data as $k => $v ) {
                $selected = ( in_array( $v, $comparison ) ? ' selected="selected"' : null );
                ?>
                <option value="<?php esc_attr_e( $k ); ?>" <?php echo $selected; ?>>
                    <?php esc_attr_e( $v ); ?>
                </option>
            <?php
            }
            ?>
            <script type="text/javascript">
                jQuery("#e20r-setting-select-<?php esc_attr_e( $name ); ?>").select2({
                    placeholder: "Click to select",
                    allowClear: true
                });
            </script>
        </td>
    <?php
    }
}