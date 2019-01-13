---
title: Contact
nocache: 1
twig: 1
simplecontact:
    subject: "Subject"
    recipient: "blackhole@example.com"
    fields:
      name:
        label: "Your name"
        placeholder:
      email:
        label: "Your email"
        placeholder:
      message:
        label: "Your message"
        placeholder:
      antispam:
        label: "Antispam"
        placeholder:
      submit:
        label: "Send form"
    messages:
      success: "Thank you! Please note that your message was not sent."
      error: "Oops! There was a problem. Please enter all fields below and try again."
      fail: "Oops!  There was a problem. The message could not be sent."
    errors:
      empty_field: "Required field"
      invalid_email: "The given email is invalid"
---

# Contact

{{ simplecontact() }}


--- sidebar ---

### Adresse

John Doe    
3020 Basel
Switzerland

<mail@johndoe.com>  
<http://www.example.com>  
