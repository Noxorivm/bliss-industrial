<?php
  /**
   * Requires the "PHP Email Form" library
   * The library should be uploaded to: assets/vendor/php-email-form/php-email-form.php
   * (Path relative to the document root)
   */

  // Receiving email address (Your BLISS email)
  $receiving_email_address = 'hola@blissindustrial.eu';

  // Construct the path to the library relative to this script's location
  // If this script is in /forms/, the library is ../assets/vendor...
  $php_email_form_path = '../assets/vendor/php-email-form/php-email-form.php';

  if( file_exists($php_email_form_path) ) {
    include( $php_email_form_path );
  } else {
    // Provide a more user-friendly error if possible, or log it.
     die( 'Error: No se pudo cargar la librería de envío de correo. Ruta verificada: ' . realpath(dirname(__FILE__)) . '/' . $php_email_form_path);
  }

  // --- Get data from POST request ---
  // The name attribute in the footer form is 'email_newsletter'
  $subscriber_email = isset($_POST['email_newsletter']) ? $_POST['email_newsletter'] : null;

  // Basic validation
  if (!$subscriber_email || !filter_var($subscriber_email, FILTER_VALIDATE_EMAIL)) {
      die('Por favor, introduce una dirección de correo electrónico válida.');
  }

  // --- Create and configure the email object ---
  $contact = new PHP_Email_Form;
  $contact->ajax = true; // Use AJAX response (required by validate.js)

  $contact->to = $receiving_email_address;
  // Use the subscriber email for From Name and From Email (common for subscriptions)
  $contact->from_name = $subscriber_email;
  $contact->from_email = $subscriber_email;
  $contact->subject ="Nueva Suscripción Newsletter - BLISS: " . $subscriber_email; // Clear subject

  // --- SMTP Configuration (Optional - same as contact.php) ---
  /*
  $contact->smtp = array(
    'host' => 'your_smtp_host.com',
    'username' => 'your_smtp_username',
    'password' => 'your_smtp_password',
    'port' => '587',
    'encryption' => 'tls'
  );
  */

  // --- Build the email message body ---
  $contact->add_message( $subscriber_email, 'Email Suscrito');

  // --- Send the email and output result ---
  echo $contact->send();
?>