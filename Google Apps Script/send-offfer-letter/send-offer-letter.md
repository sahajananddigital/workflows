You can automate the process of generating and sending offer letters using Google Apps Script by following these steps:

### Steps to Automate Offer Letter Distribution
1. **Prepare a Google Sheet**:
   - Create columns for `Name`, `Email`, and any other required data (e.g., position, start date).
   
2. **Create a Google Doc Template**:
   - Design your offer letter in Google Docs.
   - Use placeholders (e.g., `{{Name}}`, `{{Position}}`) for dynamic content.

3. **Write the Apps Script**:
   - The script will:
     1. Read data from the Google Sheet.
     2. Replace placeholders in the template with actual data.
     3. Generate a PDF for each offer letter.
     4. Email the PDF to each intern.

4. **Deploy and Run**:
   - Trigger the script manually or set up a time-based trigger.

### Sample Google Apps Script Code
Here’s an example to get you started:

```javascript
function sendOfferLettersWithDates() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Active'); // Sheet name
  const templateDocId = '1jm5a9KIA83xd3vn_qT22UdJYoS7WLnqx5p5vllH2-Yk'; // Replace with your template ID
  const subject = 'Your Internship Offer Letter';
  const emailBody = 'Dear {{Full Name}},\n\nPlease find your offer letter attached.\n\nBest regards,\nSahajanand Digital';

  // Specify the range: startRow and endRow (1-based index, including headers)
  const startRow = 7; // Start processing from row 2 (after headers)
  const endRow = 11;  // End processing at row 10
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0]; // Get headers
  
  // Get the specified range of data
  const data = sheet.getRange(startRow, 1, endRow - startRow + 1, sheet.getLastColumn()).getValues();

  // Add today's date in desired format
  const today = new Date();
  const currentDate = Utilities.formatDate(today, Session.getScriptTimeZone(), "MMMM d, yyyy");

  // Map data for easier access and process each row
  data.forEach(row => {
    const record = headers.reduce((obj, header, index) => {
      obj[header] = row[index];
      return obj;
    }, {});

    // Ensure Start Date and End Date exist in your sheet headers
    const startDate = record['Start Date'] 
      ? Utilities.formatDate(new Date(record['Start Date']), Session.getScriptTimeZone(), "MMMM d, yyyy") 
      : 'Not Specified';
    const endDate = record['End Date'] 
      ? Utilities.formatDate(new Date(record['End Date']), Session.getScriptTimeZone(), "MMMM d, yyyy") 
      : 'Not Specified';

    // Create a new document from the template
    const doc = DriveApp.getFileById(templateDocId).makeCopy().getId();
    const docFile = DocumentApp.openById(doc);
    const body = docFile.getBody();

    // Replace placeholders with actual data
    Object.keys(record).forEach(key => {
      body.replaceText(`{{${key}}}`, record[key]);
    });
    body.replaceText('{{Current Date}}', currentDate);
    body.replaceText('{{Start Date}}', startDate);
    body.replaceText('{{End Date}}', endDate);

    docFile.saveAndClose();

    // Convert the document to PDF
    const pdf = DriveApp.getFileById(doc).getAs('application/pdf');
    DriveApp.getFileById(doc).setTrashed(true); // Delete the temporary doc

    // Send email with the PDF attachment
    GmailApp.sendEmail(record['Email Address'], subject, emailBody.replace('{{Full Name}}', record['Full Name']), {
      attachments: [pdf]
    });
  });
}
```

### Notes:
- Replace `YOUR_TEMPLATE_DOC_ID` with the actual Google Doc template ID.
- Ensure your placeholders in the template match the column headers in the sheet.

### How to Use
1. **Adjust the Range**:
   - Modify `startRow` and `endRow` to set the range of rows you want to process.

2. **Test the Script**:
   - Run the script for a small range first to ensure it works as expected.

3. **Notes**:
   - The range is inclusive, so `startRow` to `endRow` will include all rows within this range.
   - Ensure the column names in your Google Sheet match the placeholders in the template.

### Steps to Use:
1. **Template Placeholders**:
   - Include placeholders `{{Current Date}}`, `{{Start Date}}`, and `{{End Date}}` in your Google Doc template.
   - Example: *"This offer is effective from {{Start Date}} to {{End Date}}."*

2. **Google Sheet Setup**:
   - Ensure your Google Sheet has `Start Date` and `End Date` columns. If these are missing, the script will use `Not Specified` as a default value.

3. **Date Format**:
   - The dates will be formatted as `Month Day, Year` (e.g., *December 16, 2024*). You can adjust this format by modifying the `Utilities.formatDate` function.

4. **Testing**:
   - Test the script with a small range to verify that placeholders are correctly replaced.

---

Let me know if you need more features or adjustments!