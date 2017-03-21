<?php

declare(strict_types = 1);

namespace Drupal\field_ipaddress\Plugin\Field\FieldType;

use Drupal\Core\Field\{
  FieldDefinitionInterface,
  FieldItemBase,
  FieldStorageDefinitionInterface
};
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ipaddress' field type.
 *
 * @FieldType(
 *   id = "ipaddress",
 *   label = @Translation("IP Address"),
 *   description = @Translation("Create and store IP addresses or ranges."),
 *   default_widget = "ipaddress_default",
 *   default_formatter = "ipaddress_default"
 * )
 */
class IpAddress extends FieldItemBase implements IpAddressInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];

    $properties['ip'] = DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('IP address'))
        ->setDescription(new TranslatableMarkup('The base IP address'));

    $properties['ip_cidr'] = DataDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('IP address cidr'))
        ->setDescription(new TranslatableMarkup('If the IP range is stored as cidr mask then this field holds the mask'))
        ->setSetting('unsigned', TRUE)
        ->setComputed(TRUE);

    $properties['ip_end'] = DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('End address'))
        ->setDescription(new TranslatableMarkup('If an IP range was provided then this field will hold the "end" of the range'))
        ->setComputed(TRUE);

    //-- Store the ranges itself.
    $properties['ip_from'] = DataDefinition::create('any')
        ->setLabel(t('IP value minimum'))
        ->setDescription(t('The IP minimum value, as a binary number.'));

    $properties['ip_to'] = DataDefinition::create('any')
        ->setLabel(t('IP value maximum'))
        ->setDescription(t('The IP maximum value, as a binary number.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * IPs are stored in the "longest" format. If a "CIDR" notation is used then
   * the mask is stored in a separate field.
   *
   * @see \Darsyn\IP\IP::getLongAddress();
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'ip' => [
          'description' => 'The base IP address, is also used as the "start" of the range.',
          'type' => 'varchar',
          'length' => 39,
          'binary' => FALSE,
        ],
        'ip_cidr' => [
          'description' => 'The CIDR mask if the range is provided with one.',
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
        ],
        'ip_end' => [
          'description' => 'The plain text value of the end of the ip range',
          'type' => 'varchar',
          'length' => 39,
          'binary' => FALSE,
        ],
        'ip_from' => [
          'description' => 'The minimum IP address stored as a binary number.',
          'type' => 'blob',
          'size' => 'tiny',
          'mysql_type' => 'varbinary(16)',
          'not null' => TRUE,
          'binary' => TRUE
        ],
        'ip_to' => [
          'description' => 'The maximum IP address stored as a binary number.',
          'type' => 'blob',
          'size' => 'tiny',
          'mysql_type' => 'varbinary(16)',
          'not null' => TRUE,
          'binary' => TRUE
        ],
      ],
      'indexes' => [
        'ip' => [
          'ip',
        ],
        'ip_end' => [
          'ip_end',
        ],
        'ip_from' => [
          'ip_from',
        ],
        'ip_to' => [
          'ip_to',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'ip';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // If the "ip" isn't set then this field is empty.
    $ip = $this->get('ip')->getValue();
    return $ip === NULL || $ip === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition = NULL) {
    $values = [
      // Always fill the base ip.
      'ip' => self::ipv4(),
      'ip_cidr' => 32,
      'ip_end' => '',
      'ip_from' => '',
      'ip_to' => '',
    ];

    // Cidr, range or one value?
    $type = 'single';
    switch (rand(0, 2)) {
      case 0:
        $type = 'cidr';
        $values['ip_cidr'] = rand(0, 32);
        break;
      case 1:
        $type = 'range';
        $values['ip_end'] = self::ipv4();
        break;
    }

    // Set the range values.
    switch ($type) {
      case 'cidr':
        $subnet = new \IPv4\SubnetCalculator($values['ip'], $values['ip_cidr']);
        list($values['ip_from'], $values['ip_to']) = $subnet->getIPAddressRange();
        break;

      case 'single':
        $values['ip_from'] = $values['ip'];
        break;

      case 'range':
        $values['ip_from'] = $values['ip'];
        $values['ip_to'] = $values['ip_end'];
        break;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawIP(): string {
    $raw = '';

    $base_ip = $this->get('ip')->getValue();
    $cidr = $this->get('ip_cidr')->getValue();
    $end_ip = $this->get('ip_end')->getValue();

    // Always the base.
    $raw .= $base_ip;

    // CIDR?
    if (!empty($cidr)) {
      $raw .= "/{$cidr}";
    }
    elseif (!empty($end_ip)) {
      $raw .= " - {$end_ip}";
    }

    return $raw;
  }

  /**
   * Generate a random IPv4 address.
   *
   * @return string
   *   A random valid IPv4 address.
   */
  protected static function ipv4(): string {
    return mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
  }

}
