<?php
/**
 * Custom Table Formatter with word wrapping and terminal width optimization.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @since   3.0.0
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom table formatter that provides enhanced table display functionality.
 *
 * @class CoCart\JWTAuthentication\Table_Formatter
 */
class Table_Formatter {

	/**
	 * Pretty print a table with word wrapping and terminal width optimization.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param array $items           Array of associative arrays representing rows.
	 * @param array $fields          Fields to display in the table.
	 * @param int   $wrap_width      Maximum width before wrapping text.
	 * @param array $variable_fields Optional array of field names that should use wrap width.
	 *
	 * @return void
	 */
	public static function prettier_table( $items, $fields, $wrap_width = 20, $variable_fields = array( 'pat', 'token' ) ) {
		if ( empty( $items ) ) {
			\WP_CLI::line( 'No items.' );
			return;
		}

		$enabled = \WP_CLI::get_runner()->in_color();
		if ( $enabled ) {
			\WP_CLI\cli\Colors::disable( true );
		}

		// Get terminal width for dynamic sizing.
		$terminal_width = self::get_terminal_width();

		// Calculate column widths with terminal width consideration.
		$col_widths = self::calculate_column_widths( $items, $fields, $wrap_width, $terminal_width, $variable_fields );

		// Build separator line.
		$separator = '+';

		foreach ( $col_widths as $width ) {
			$separator .= str_repeat( '-', $width + 2 ) . '+';
		}

		// Print header.
		\WP_CLI::line( $separator );

		$header = '|';

		foreach ( $fields as $field ) {
			$header .= ' ' . str_pad( $field, $col_widths[ $field ] ) . ' |';
		}

		\WP_CLI::line( $header );
		\WP_CLI::line( $separator );

		// Print rows with word-aware wrapping.
		foreach ( $items as $item ) {
			self::print_row( $item, $fields, $col_widths, $wrap_width );
			\WP_CLI::line( $separator );
		}

		// Restore color settings.
		if ( $enabled ) {
			\WP_CLI\cli\Colors::disable( false );
		}
	} // END prettier_table()

	/**
	 * Get terminal width using multiple detection methods.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return int Terminal width in characters.
	 */
	private static function get_terminal_width() {
		$width = null;

		// Method 1: Environment variables (works on all systems).
		$width = (int) ( $_ENV['COLUMNS'] ?? $_SERVER['COLUMNS'] ?? 0 );

		// Method 2: Windows mode command (for Windows systems).
		if ( ! $width && function_exists( 'exec' ) && PHP_OS_FAMILY === 'Windows' ) {
			$output     = array();
			$return_var = null;

			exec( 'mode con 2>nul', $output, $return_var );

			if ( 0 === $return_var ) {
				foreach ( $output as $line ) {
					if ( preg_match( '/Columns:\s*(\d+)/', $line, $matches ) ) {
						$width = (int) $matches[1];
						break;
					}
				}
			}
		}

		// Method 3: tput (Unix/Linux/Mac) - only try if not Windows.
		if ( ! $width && function_exists( 'exec' ) && PHP_OS_FAMILY !== 'Windows' ) {
			$output     = array();
			$return_var = null;

			exec( 'tput cols 2>/dev/null', $output, $return_var );

			if ( 0 === $return_var && ! empty( $output[0] ) && is_numeric( $output[0] ) ) {
				$width = (int) $output[0];
			}
		}

		// Default fallback.
		return $width > 20 ? $width : 120; // Increased default for modern terminals.
	} // END get_terminal_width()

	/**
	 * Calculate column widths considering terminal width.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param array $items           Array of items.
	 * @param array $fields          Array of field names.
	 * @param int   $wrap_width      Fixed wrap width.
	 * @param int   $terminal_width  Terminal width.
	 * @param array $variable_fields Fields that should use wrap width.
	 *
	 * @return array Column widths indexed by field name.
	 */
	private static function calculate_column_widths( $items, $fields, $wrap_width, $terminal_width, $variable_fields ) {
		$col_widths = array();

		// Initialize with field name lengths.
		foreach ( $fields as $field ) {
			$col_widths[ $field ] = strlen( $field );
		}

		// Check content - find maximum content length for each field.
		$max_content_lengths = array();

		foreach ( $items as $item ) {
			foreach ( $fields as $field ) {
				$value                         = (string) ( $item[ $field ] ?? '' );
				$value_length                  = strlen( $value );
				$max_content_lengths[ $field ] = max( $max_content_lengths[ $field ] ?? 0, $value_length );
			}
		}

		// Set initial column widths - use wrap_width as starting point for variable fields.
		foreach ( $fields as $field ) {
			if ( in_array( $field, $variable_fields ) ) {
				// Variable fields start with wrap_width, will expand later if terminal allows.
				$col_widths[ $field ] = max( $col_widths[ $field ], $wrap_width );
			} else {
				// Fixed fields use actual content length.
				$col_widths[ $field ] = max( $col_widths[ $field ], $max_content_lengths[ $field ] );
			}
		}

		// Adjust column widths based on terminal width.
		$total_padding = ( count( $fields ) + 1 ) * 2 + count( $fields ) - 1;
		$total_width   = array_sum( $col_widths ) + $total_padding;

		if ( $total_width > $terminal_width ) {
			// Table is too wide - smart compression.
			$available_width = $terminal_width - $total_padding;

			// Calculate how much space we need for fixed fields and short variable fields.
			$fixed_space_needed  = 0;
			$compressible_fields = array();

			foreach ( $fields as $field ) {
				$max_content   = $max_content_lengths[ $field ];
				$header_length = strlen( $field );

				if ( in_array( $field, $variable_fields ) && $max_content > $wrap_width ) {
					// Long variable fields are compressible.
					$compressible_fields[ $field ] = $col_widths[ $field ];
				} else {
					// Fixed fields and short variable fields keep their natural size (at least header width).
					if ( in_array( $field, $variable_fields ) ) {
						$natural_size = max( $max_content, $header_length );
					} else {
						$natural_size = max( $col_widths[ $field ], $header_length );
					}

					$col_widths[ $field ] = $natural_size;
					$fixed_space_needed  += $natural_size;
				}
			}

			// Distribute remaining space among compressible fields.
			$remaining_space = $available_width - $fixed_space_needed;

			if ( $remaining_space > 0 && ! empty( $compressible_fields ) ) {
				$total_compressible = array_sum( $compressible_fields );

				foreach ( $compressible_fields as $field => $original_width ) {
					$field_proportion     = $original_width / $total_compressible;
					$col_widths[ $field ] = max( 20, (int) ( $remaining_space * $field_proportion ) );
				}
			}
		} else {
			// Terminal is wide enough - optimize naturally.

			// First pass: Set all fields to their natural/minimum sizes.
			foreach ( $fields as $field ) {
				$max_content   = $max_content_lengths[ $field ];
				$header_length = strlen( $field );

				if ( in_array( $field, $variable_fields ) ) {
					// Variable fields: use content size or wrap_width minimum for long content.
					if ( $max_content <= $wrap_width ) {
						// Short content uses natural size, but at least as wide as header.
						$col_widths[ $field ] = max( $max_content, $header_length );
					} else {
						// Long content starts at wrap_width.
						$col_widths[ $field ] = max( $wrap_width, $header_length );
					}
				} else {
					// Fixed fields use natural size, but at least as wide as header.
					$col_widths[ $field ] = max( $max_content, $header_length );
				}
			}

			// Recalculate available space after natural sizing.
			$natural_total   = array_sum( $col_widths ) + $total_padding;
			$available_extra = $terminal_width - $natural_total;

			// Second pass: Expand only fields that need it (long content).
			if ( $available_extra > 20 ) {
				foreach ( $fields as $field ) {
					if ( in_array( $field, $variable_fields ) ) {
						$max_content   = $max_content_lengths[ $field ];
						$current_width = $col_widths[ $field ];

						// Only expand fields with content longer than current width.
						if ( $max_content > $current_width && $available_extra > 0 ) {
							$target_width      = min( $max_content, 100 ); // Cap at 100 characters.
							$expansion_needed  = $target_width - $current_width;
							$expansion_allowed = min( $expansion_needed, (int) ( $available_extra * 0.9 ) );

							$col_widths[ $field ] += $expansion_allowed;
							$available_extra      -= $expansion_allowed;
						}
					}
				}
			}
		}

		return $col_widths;
	} // END calculate_column_widths()

	/**
	 * Print a table row with word-aware wrapping.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param array $item       Item data.
	 * @param array $fields     Field names.
	 * @param array $col_widths Column widths.
	 * @param int   $wrap_width Wrap width.
	 */
	private static function print_row( $item, $fields, $col_widths, $wrap_width ) {
		$wrapped_row = array();
		$max_lines   = 1;

		// Prepare wrapped content for each field.
		foreach ( $fields as $field ) {
			$value        = (string) ( $item[ $field ] ?? '' );
			$target_width = $col_widths[ $field ]; // Use actual column width, not wrap_width.

			if ( strlen( $value ) > $target_width ) {
				// Word-aware wrapping using the actual column width.
				$lines = explode( "\n", wordwrap( $value, $target_width, "\n", true ) );
			} else {
				$lines = array( $value );
			}

			$wrapped_row[ $field ] = $lines;
			$max_lines             = max( $max_lines, count( $lines ) );
		}

		// Print each line of the row.
		for ( $i = 0; $i < $max_lines; $i++ ) {
			$line = '|';

			foreach ( $fields as $field ) {
				$cell_line = $wrapped_row[ $field ][ $i ] ?? '';
				$line     .= ' ' . str_pad( $cell_line, $col_widths[ $field ] ) . ' |';
			}

			\WP_CLI::line( $line );
		}
	} // END print_row()
} // END class
