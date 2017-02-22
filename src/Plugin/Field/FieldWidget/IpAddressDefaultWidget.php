<?php

declare(strict_types = 1);

namespace Drupal\field_ipaddress\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Description of IpAddressDefaultWidget
 *
 * @FieldWidget(
 *   id = "ipaddress_default",
 *   label = @Translation("IP address default"),
 *   field_types = {
 *     "ipaddress"
 *   }
 * )
 * @author erikfrerejean
 */
class IpAddressDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = [
      '#type' => 'ipaddress',
      '#title' => $this->t('Ip address or range'),
      '#description' => $this->t('Provide an IP address or range, ranges can used as "CIDR" <em>(127.0.0.1/24)</em> or seperated with a dash <em>(127.0.0.1 - 127.0.0.254)</em>'),
      '#default_value' => $items[$delta]->getRawIP(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Convert to storage format
    foreach ($values as &$item) {
      $value = trim($item['value']);
      if (!empty($value)) {
        $ranges = \Drupal\field_ipaddress\Element\IPAddressTextField::extractRangeFromValue($value);
        array_walk($ranges, 'trim');
        list($ip_start, $ip_end, $cidr) = $ranges;

        $item['ip'] = trim($ip_start);
        if (!empty($cidr)) {
          $item['ip_cidr'] = $cidr;
        }
        $item['ip_end'] = $ip_end ? trim($ip_end) : '';
        $item['ip_from'] = inet_pton($item['ip']);
        $item['ip_to'] = !empty($item['ip_end']) ? inet_pton($item['ip_end']) : $item['ip_from'];
      }
    }

    return $values;
  }

}
