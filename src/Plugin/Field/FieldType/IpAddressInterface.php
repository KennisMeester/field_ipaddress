<?php

declare(strict_types = 1);

namespace Drupal\field_ipaddress\Plugin\Field\FieldType;

/**
 *
 * @author erikfrerejean
 */
interface IpAddressInterface {

  /**
   * Get the IP input value.
   *
   * Convert the current IP value back into the value that was inserted in the
   * form. This reapplies the CIDR mask or glues back the range.
   *
   * @return string
   *   The raw input.
   */
  public function getRawIP(): string;
}
