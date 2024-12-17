Here’s the updated script with an option to specify a range of rows to process. This allows you to control which rows in the Google Sheet are imported into Google Contacts.

---

### Script with Range Option for Importing Contacts

```javascript
function importContactsToGoogleWithRange() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Active'); // Sheet name
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0]; // Get headers
  
  // Specify the range: startRow and endRow (1-based index, including headers)
  const startRow = 2; // Start processing from row 2 (after headers)
  const endRow = 10;  // End processing at row 10

  const data = sheet.getRange(startRow, 1, endRow - startRow + 1, sheet.getLastColumn()).getValues();

  // Required columns: Name, Email, Phone (optional)
  const nameColumn = headers.indexOf('Full Name');
  const emailColumn = headers.indexOf('Email Address');
  const phoneColumn = headers.indexOf('WhatsApp Number'); // Optional

  if (nameColumn === -1 || emailColumn === -1) {
    throw new Error("The 'Name' and 'Email' columns are required in the sheet.");
  }

  data.forEach(row => {
    const name = `SD intern ${row[nameColumn]} 2024`;
    const email = row[emailColumn];
    const phone = phoneColumn !== -1 ? row[phoneColumn] : null;

    if (name && email) {
      // Check if the contact already exists
      const contacts = ContactsApp.getContactsByEmailAddress(email);
      let contact;

      if (contacts.length > 0) {
        // If contact exists, update it
        contact = contacts[0];
        contact.setFullName(name);
      } else {
        // If contact does not exist, create a new one
        contact = ContactsApp.createContact(name, email, phone || '');
      }

      // Add or update phone number if provided
      if (phone) {
        const phones = contact.getPhones();
        if (phones.length > 0) {
          phones[0].setPhoneNumber(phone); // Update the first phone number
        } else {
          contact.addPhone(ContactsApp.Field.MOBILE_PHONE, phone);
        }
      }
    }
  });

  SpreadsheetApp.getUi().alert(`Contacts from rows ${startRow} to ${endRow} have been imported/updated successfully.`);
}
```

---

### Steps to Use:
1. **Adjust the Range**:
   - Set `startRow` and `endRow` in the script to define the range of rows to process. These are 1-based indexes, including the header row.

2. **Run the Script**:
   - Save the script and run `importContactsToGoogleWithRange`.
   - Provide permissions when prompted.

3. **Test the Script**:
   - Run the script for a small range first to ensure it works as expected.

---

### Customization Ideas:
- **Dynamic Range Selection**: Implement a prompt to let users input the `startRow` and `endRow` dynamically during script execution.
- **Logging**: Log the details of imported/updated contacts into a new sheet or in the Apps Script Logger (`Logger.log`).

---

Let me know if you’d like me to add any of these enhancements!