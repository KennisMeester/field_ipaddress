<?php

declare(strict_types = 1);

namespace Drupal\field_ipaddress\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Description of IPAddressTextField
 *
 * @FormElement("ipaddress")
 * @author erikfrerejean
 */
class IPAddressTextField extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    $info = parent::getInfo();
    $info['#element_validate'] = [
      [$class, 'validateIPAddress'],
    ];
    $info['#maxlength'] = 36;
    $info['#placeholder'] = '127.0.0.1/32';
    $info['#allow_range'] = TRUE;
    $info['#allow_cidr'] = TRUE;

    return $info;
  }

  /**
   * Form element validation handler for #type 'color'.
   */
  public static function validateIPAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);

    if (empty($value)) {
      return;
    }

    // Quick validate.
    if (FALSE !== stripos($value, '-') && empty($element['#allow_range'])) {
      $form_state->setError($element, t('IP addresses with CIDR mask are not allowed.'));
    }

    if (FALSE !== stripos($value, '/')) {
      if (empty($element['#allow_cidr'])) {
        $form_state->setError($element, t('IP addresses with CIDR mask are not allowed.'));
      }
      else {
        list(, $cidr) = explode('/', $value);
        if (!is_numeric($cidr) || $cidr < 0 || $cidr > 32) {
          $form_state->setError($element, t('The provided network mask is not valid.'));
        }
      }
    }

    // Extract the values and validate.
    list($ip_start, $ip_end) = self::extractRangeFromValue($value);

    if (empty($ip_start)) {
      $form_state->setError($element, t('No base IP set.'));
    }
    elseif (!filter_var(trim($ip_start), FILTER_VALIDATE_IP)) {
      $form_state->setError($element, t('Invalid IP provided.'));
    }

    if (!empty($ip_end) && !filter_var(trim($ip_end), FILTER_VALIDATE_IP)) {
      $form_state->setError($element, t('Invalid IP range provided'));
    }
  }

  /**
   * Extract the start and end values of the input.
   *
   * @param string $value
   *   The value to extract the range from.
   *
   * @retun array
   *   An array containing the beginning and end of the range.
   */
  public static function extractRangeFromValue(string $value): array {
    $ip_start = $ip_end = NULL;
    if (FALSE !== stripos($value, '-')) {
      $exploded = explode('-', $value);
      array_walk($exploded, 'trim');
      list($ip_start, $ip_end) = $exploded;
    }
    elseif (FALSE !== stripos($value, '/')) {
      list($base_ip, $cidr) = explode('/', $value);
      $subnet = new \IPv4\SubnetCalculator($base_ip, $cidr);
      $ip_start = $base_ip;
      list(, $ip_end) = $subnet->getIPAddressRange();
    }
    else {
      $ip_start = $value;
    }

    return [
      $ip_start,
      $ip_end,
      $cidr ?? FALSE,
    ];
  }

}
