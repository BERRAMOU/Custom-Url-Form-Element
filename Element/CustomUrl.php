<?php

namespace Drupal\d8api\Element;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use InvalidArgumentException;

/**
 * Provides a form element for input of a URL.
 *
 * Properties:
 * - #default_value: A valid URL string.
 * - #size: The size of the input element in characters.
 *
 * Usage example:
 *
 * @code
 * $form['homepage'] = array(
 *   '#type' => 'custom_url',
 *   '#title' => $this->t('Home Page'),
 *   '#size' => 30,
 *   ...
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("custom_url")
 */
class CustomUrl extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input'                   => TRUE,
      '#size'                    => 60,
      '#maxlength'               => 255,
      '#autocomplete_route_name' => FALSE,
      '#process'                 => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#element_validate'        => [
        [$class, 'validateCustomUrl'],
      ],
      '#pre_render'              => [
        [$class, 'preRenderCustomUrl'],
      ],
      '#theme'                   => 'input__textfield',
      '#theme_wrappers'          => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for #type 'custom_url'.
   *
   * Note that #maxlength and #required is validated by _form_validate()
   * already.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateCustomUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && UrlHelper::isExternal($value) && !UrlHelper::isValid($value, TRUE)) {
      $form_state->setError($element, t('The External URL %url is not valid.', ['%url' => $value]));
    }
    // Validate internal url : Should be Like /node/nid
    if ($value !== '' && !UrlHelper::isExternal($value) && !self::isInternalUrlValid($value)) {
      $form_state->setError($element, t('The Internal URL %url is not valid.', ['%url' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      try {
        if (self::isInternalUrlValid($input)) {
          return Url::fromUserInput($input, ['absolute' => 'true'])->toString();
        }
        else {
          return $input;
        }
      } catch (InvalidArgumentException $argumentException) {
        return NULL;
      }
    }

    return NULL;
  }

  /**
   * Prepares a #type 'url' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCustomUrl($element) {
    $element['#attributes']['type'] = 'text';
    $element['#attributes']['placeholder'] = t('External Url example http://example.com Or Internal Url /node/nid');
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
      'placeholder',
    ]);
    static::setAttributes($element, ['form-url']);

    return $element;
  }

  /**
   * check if url node/nid is valid url
   *
   * @param $url
   *
   * @return bool
   */
  public static function isInternalUrlValid($url) {
    if (!UrlHelper::isExternal($url) && preg_match('/\/node\/(\d+)/', $url, $matches)) {
      $node = Node::load($matches[1]);
      return ($node) ? TRUE : FALSE;
    }
    else {
      return FALSE;
    }
  }

}
