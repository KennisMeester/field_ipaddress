<?php

declare(strict_types = 1);

namespace Drupal\field_ipaddress\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\{
  FieldItemListInterface,
  FormatterBase
};

/**
 * Description of IpAddressDefaultFormatter
 *
 * @FieldFormatter(
 *   id = "ipaddress_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "ipaddress"
 *   }
 * )
 * @author erikfrerejean
 */
class IpAddressDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
      $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->getRawIP(),
      ];
    }

    return $elements;
  }

}
