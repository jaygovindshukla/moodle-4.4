# local_brevo_mail

Reusable Brevo mail integration for Moodle.

## Setup

1. Visit **Site administration > Notifications** to install the plugin.
2. Open **Site administration > Plugins > Local plugins > Brevo mail**.
3. Set:
   - Brevo API key
   - Use Brevo for Moodle core email (optional, for all `email_to_user()` mail)
   - Fallback to Moodle native mail (recommended ON)
   - Default sender email
   - Default sender name

## Usage in Moodle code

### Send one email

```php
require_once($CFG->dirroot . '/local/brevo_mail/lib.php');

$result = local_brevo_mail_send_email(
    'student@example.com',
    'Student Name',
    'Welcome',
    '<p>Your account is ready.</p>'
);
```

### Send same email to multiple users

```php
require_once($CFG->dirroot . '/local/brevo_mail/lib.php');

$recipients = [
    ['email' => 'a@example.com', 'name' => 'User A'],
    ['email' => 'b@example.com', 'name' => 'User B'],
];

$results = local_brevo_mail_send_bulk(
    $recipients,
    'Course reminder',
    '<p>Please complete your activity.</p>'
);
```

### Use class directly (advanced)

```php
$mailer = new \local_brevo_mail\mailer();
$result = $mailer->send_email(
    'user@example.com',
    'User',
    'Subject',
    '<p>HTML body</p>'
);
```

## Route Moodle core mail through Brevo API

Enable `Use Brevo for Moodle core email` in plugin settings.

When enabled:
- `email_to_user()` tries Brevo API first.
- If Brevo fails and fallback is ON, Moodle uses native mailer next.
- If fallback is OFF, the send fails immediately with Brevo error.
