LabConnect - Medical Analysis Management Platform
Overview
This project was developed as part of the PW (Projet Technologies Web) – 2nd Year Engineering Program at Esprit School of Engineering (Academic Year 2026–2027).
LabConnect is a web platform that supports the patient before, during and after a medical analysis: laboratory search, appointment booking, online results with health tracking, notifications and complaints.
Goal: simplify the patient journey and the management of laboratories.
Problem solved: avoid unnecessary travel and waiting by letting patients compare laboratories, book a time slot and consult their results online.
Table of Contents
Overview
Features
Tech Stack
Architecture
Contributors
Academic Context
Getting Started
Acknowledgments
Features
Front Office (Patient Area)
Laboratory search and price comparison
Appointment booking by time slot (with QR code)
Online results and health evolution tracking
Complaint management and follow-up
Home sampling requests
Back Office (Administration Area)
Management of laboratories and medical analyses
Validation and management of time slots
Follow-up of patients' appointments
Management of notifications and automatic e-mail reminders
Tech Stack
Frontend
HTML, CSS, Bootstrap, Tailwind CSS, JavaScript
Backend
PHP (MVC architecture)
MySQL
Architecture
MVC architecture: `Controllers`, `Models`, `Views` (partials, layout, pages), with a clear separation between the Front Office and the Back Office.
Contributors
Name	Module
Farah Ghanney	Appointments (Rendez-vous)
Meriam Filali	Laboratories
Iyed Mhadhbi	Results
Amine Dorai	Complaints
Ahmed Bnmakki	
Yessine Ben Taleb	Home sampling
Academic Context
Developed at Esprit School of Engineering – Tunisia
PW – 2A10 | 2026–2027
Getting Started
Clone the repository:
```bash
   git clone https://github.com/farah1300/Esprit-PW-2A10-2026-LabConnect.git
   cd Esprit-PW-2A10-2026-LabConnect
   ```
If you use WAMP or XAMPP:
Place the project in the `www` (WAMP) or `htdocs` (XAMPP) folder.
Start Apache and MySQL from the WAMP/XAMPP interface.
Open the project at http://localhost/Esprit-PW-2A10-2026-LabConnect.
Check your PHP installation:
```bash
   php -v
   ```
Appointments module (HTML/CSS prototype)
The `labconnect-rdv/` folder contains the prototype of the Appointments module. Open `labconnect-rdv/index.html` in a browser to view it.
`front/`: `rdv_start`, `login`, `register`, `rdv_create`, `rdv_mine`, `rdv_detail`
`back/`: `rendezvous` (list with status filter), `rendezvous_form`, `rendezvous_detail`
`public/`: `css`, `js`, `img`
Patient flow: sign up → book an appointment (analysis + time slot + optional prescription) → My appointments (status En attente until confirmed, QR code issued once confirmed, cancellation possible).
Acknowledgments
We would like to thank Esprit School of Engineering and our professors and tutors for their guidance and support throughout this project.
