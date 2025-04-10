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

  // --- Get data from POST request, ensuring keys exist ---
  $nombre_contacto = isset($_POST['nombre_contacto']) ? $_POST['nombre_contacto'] : 'No proporcionado';
  $correo = isset($_POST['correo']) ? $_POST['correo'] : 'No proporcionado';
  $nombre_empresa = isset($_POST['nombre_empresa']) ? $_POST['nombre_empresa'] : 'No proporcionada';
  $sector_empresa = isset($_POST['sector_empresa']) ? $_POST['sector_empresa'] : 'No seleccionado';
  $otro_sector = isset($_POST['otro_sector']) ? $_POST['otro_sector'] : ''; // Might be empty
  $localizacion = isset($_POST['localizacion']) ? $_POST['localizacion'] : 'No proporcionada';
  $servicio_interes = isset($_POST['servicio_interes']) ? $_POST['servicio_interes'] : 'No seleccionado';
  $mensaje = isset($_POST['mensaje']) ? $_POST['mensaje'] : 'Sin mensaje.';

  // --- Create and configure the email object ---
  $contact = new PHP_Email_Form;
  $contact->ajax = true; // Use AJAX response (required by validate.js)

  $contact->to = $receiving_email_address;
  $contact->from_name = $nombre_contacto; // Use the contact's name
  $contact->from_email = $correo; // Use the contact's email
  $contact->subject = "Nuevo Contacto Web - BLISS Industrial: " . $nombre_empresa; // Informative subject

  // --- SMTP Configuration (Optional) ---
  /*
  $contact->smtp = array(
    'host' => 'your_smtp_host.com',    // e.g., smtp.gmail.com or your hosting provider's SMTP
    'username' => 'your_smtp_username', // Your full email address
    'password' => 'your_smtp_password', // Your email password or App Password
    'port' => '587',                   // 587 for TLS, 465 for SSL
    'encryption' => 'tls'              // 'tls' or 'ssl'
  );
  */

  // --- Build the email message body ---
  $contact->add_message( $nombre_contacto, 'Nombre contacto');
  $contact->add_message( $correo, 'Email');
  $contact->add_message( $nombre_empresa, 'Empresa');

  // Handle "Otro" sector
  if ($sector_empresa === 'Otro' && !empty($otro_sector)) {
      $contact->add_message( $sector_empresa . ': ' . $otro_sector, 'Sector');
  } else {
      $contact->add_message( $sector_empresa, 'Sector');
  }

  $contact->add_message( $localizacion, 'Localización');
  $contact->add_message( $servicio_interes, 'Servicio de Interés');
  $contact->add_message( $mensaje, 'Mensaje', 10); // 10 = minimum lines for textarea

  // --- Send the email and output result ---
  echo $contact->send();
?>