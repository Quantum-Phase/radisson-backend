<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate</title>
    <style>
        @font-face {
            font-family: 'Lato';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url('{{ storage_path("fonts/Lato-Regular.ttf") }}') format('truetype');
        }

        @font-face {
            font-family: 'Lato Bold';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url('{{ storage_path("fonts/Lato-Bold.ttf") }}') format('truetype');
        }

        @font-face {
            font-family: 'Lato Black';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url('{{ storage_path("fonts/Lato-Black.ttf") }}') format('truetype');
        }

        @font-face {
            font-family: 'AdlamDislay';
            font-weight: normal;
            font-style: normal;
            font-variant: normal;
            src: url('{{ storage_path("fonts/ADLaMDisplay-Regular.ttf") }}') format('truetype');
        }

        @page {
            margin: 0;
            padding: 0;
            size: A4 portrait;
        }

        body {
            font-family: 'Lato', sans-serif !important;
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
        }

        .certificate-section {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .certificate-bg-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .background-image {
            width: 100%;
            height: 100%;
        }

        .gold-wheat {
            position: absolute;
            inset: 0;
            z-index: -2;
            height: 100%;
        }

        .radisson-logo {
            width: 100px;
            height: 100px;
            position: absolute;
            top: 85px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            width: 559px;
            height: 493px;
        }

        .medal-logo {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .header-container {
            position: absolute;
            top: 30px;
            right: 60px;
            z-index: 10;
            display: flex;
            flex-direction: column;
        }

        .registration-text {
            font-size: 14px;
            color: #EE2424;
            font-weight: 600;
            line-height: 10px;
        }

        .enrollment-text {
            font-size: 14px;
            color: #008AC5;
            font-weight: 600;
            line-height: 10px;
        }

        .bold-text {
            font-weight: 700;
        }

        .black-text {
            color: #000000;
        }

        .main-content {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .affiliation-text {
            font-size: 12px;
            color: #EE2424;
            font-weight: 600;
            margin: 4px;
            text-align: center;
            line-height: 10px;
        }

        .company-name {
            font-size: 36px;
            font-weight: 800;
            color: #02914E;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .contact-info {
            font-size: 13px;
            color: #EE2424;
            font-weight: bold;
            margin-top: 0.5rem;
            margin: 0;
            line-height: 20px;
            text-align: center;
        }

        .certificate-title {
            font-family: 'AdlamDislay', sans-serif !important;
            font-size: 40px;
            font-weight: bold;
            color: #EE2424;
            text-align: center;
            line-height: 16px;
            margin-bottom: 0;
            text-transform: capitalize;
        }

        .certificate-line-container {
            text-align: center;
            margin-top: -4px;
        }

        .certificate-line {
            width: 600px;
            height: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: fit-content;
        }

        .profile-image-container {
            position: relative;
            width: 115px;
            height: 115px;
            border-radius: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            background: #fff;
        }

        .profile-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 115px;
            height: 115px;
            border-radius: 10px;
            z-index: 1;
        }

        .profile-image {
            width: 111px;
            height: 111px;
            object-fit: cover;
            border-radius: inherit;
            display: block;
            position: relative;
            z-index: 2;
            padding: 2px;
        }

        .certificate-content {
            text-align: center;
        }

        .certificate-content-text {
            color: #008AC5;
            font-size: 20px;
            font-weight: 600;
            max-width: 600px;
            display: block;
            margin: 0 auto;
            margin-bottom: 0px;
        }

        .certificate-content-name {
            font-size: 24px;
            font-weight: bold;
            color: #000000;
            margin: 0 auto;
            margin-bottom: 0px;
        }

        .certificate-table {
            margin: 20px auto;
            /* Centers the table */
            border-collapse: collapse;
            width: 90%;
            /* Adjust width as needed */
        }

        .certificate-table td {
            font-size: 16px;
        }

        .certificate-table .label {
            padding: 8px 16px;
            text-align: left;
            color: #008AC5;
            font-weight: 500;
            font-size: 16px;
        }

        .certificate-table td:first-child {
            font-weight: 600;
            width: 60%;
        }

        .certificate-content-wish {
            color: #008AC5;
            font-size: 16px;
            font-weight: 600;
            display: block;
            margin: 20px auto;
            width: 86%;
        }

        .certificate-bottom {
            position: absolute;
            bottom: 100px;
            left: 60px;
            border-collapse: separate;
        }

        .input-border {
            border-bottom: 1px dashed #000000;
            width: 144px;
            text-align: center;
            font-size: 14px;
            position: relative;
        }

        .input-label {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            margin-top: -4px;
        }

        .certificate-bottom td {
            padding-right: 60px;
            /* Add padding to the right of each cell */
        }

        /* Remove padding from last cell */
        .certificate-bottom td:last-child {
            padding-right: 0;
        }

        .invisible {
            visibility: hidden;
        }

        .signature {
            width: 140px;
            height: 60px;
            position: absolute;
            z-index: 10;
            top: -35px;
            left: 10px;
        }

        .seal {
            position: absolute;
            top: -70px;
            left: 20px;
            width: 74px;
            height: 120px;
            z-index: 10;
        }
    </style>
</head>

<body>
    <section class="certificate-section">
        <div class="certificate-bg-container">
            <img src="data:image/svg+xml;base64,{{ $images['certificate_bg'] }}"
                class="background-image" />
        </div>
        <img src="data:image/svg+xml;base64,{{ $images['gold_wheat'] }}"
            alt="gold-wheat" class="gold-wheat" />

        <img src="data:image/svg+xml;base64,{{ $images['radisson_logo'] }}"
            alt="radisson-logo" class="radisson-logo" />

        <img src="{{ $images['watermark_logo'] }}" alt="watermark-logo" class="watermark-logo">

        <div class="header-container">
            <p class="registration-text">
                Regd. No.: <span class="bold-text">136884</span>
            </p>
            <p class="registration-text">
                PAN: <span class="bold-text">603509588</span>
            </p>
            <p class="enrollment-text">
                Enrollment No.: <span class="bold-text black-text">888111</span>
            </p>
        </div>

        <div class="main-content">
            <h6 class="affiliation-text">Affiliated to CTEVT of Nepal</h6>
            <h1 class="company-name">Radisson Consultancy & Hotel Training</h1>

            <p class="contact-info">
                Kalimati-13, Kathmandu, Nepal, Phone No.: 977-1-5907709
            </p>
            <p class="contact-info">
                Email: info@radissonhoteltraining.com, Website: www.radissonhoteltraining.com
            </p>

            <h2 class="certificate-title">CERTIFICATE</h2>

            <div class="certificate-line-container">
                <img src="data:image/svg+xml;base64,{{ $images['certificate_line'] }}"
                    alt="certificate-line"
                    class="certificate-line" />
            </div>

            <div class="profile-image-container">
                <img src="data:image/svg+xml;base64,{{ $images['profile_bg'] }}"
                    alt="profile-background"
                    class="profile-bg" />
                <img src="{{ $studentProfileImg ? 
                        (Str::startsWith($studentProfileImg, 'data:image') ? 
                            $studentProfileImg : 
                            'data:image/jpeg;base64,' . base64_encode(file_get_contents($studentProfileImg))
                        ) : 
                        'data:image/svg+xml;base64,' . base64_encode(file_get_contents(public_path('images/user-default.jpg'))) 
                    }}"
                    alt="profile-img"
                    class="profile-image" />
            </div>

            <div class="certificate-content">
                <p class="certificate-content-text">
                    It is hereby certified that
                </p>

                <p class="certificate-content-name">
                    {{ $gender == 'male' ? 'Mr.' : 'Ms.' }}
                    {{ $studentName }}
                </p>

                <p class="certificate-content-text" style="line-height: 20px;">
                    has successfully completed the training from out institute. {{ $gender == 'male' ? 'His' : 'Her' }} training details are as mentioned.
                </p>
            </div>

            <table class="certificate-table">
                <tbody>
                    <tr>
                        <td class="label">Course Name: </td>
                        <td class="value">{{ $courseName }}</td>
                    </tr>
                    <tr>
                        <td class="label">Training Duration: </td>
                        <td class="value">{{ $trainingDuration }}</td>
                    </tr>
                    <tr>
                        <td class="label">Training Date: </td>
                        <td class="value">({{ $trainingDate }})</td>
                    </tr>
                    <tr>
                        <td class="label">Student's Date of Birth(According to our Record): </td>
                        <td class="value">{{ $studentDateOfBirth }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="certificate-content-wish">We wish {{ $gender == 'male' ? 'him' : 'her' }} every success in {{ $gender == 'male' ? 'his' : 'her' }} future endeavors in the field of hospitality.</p>
        </div>

        <table class="certificate-bottom">
            <tr>
                <td>
                    <div class="input-border">
                        {{ $date }}
                    </div>
                    <p class="input-label">Date of Issue</p>
                </td>

                <td>
                    <div class="input-border">
                        <img src="data:image/svg+xml;base64,{{ $images['signature'] }}" alt="signature" class="signature">
                        <span class="invisible">Signature</span>
                    </div>
                    <p class="input-label">Programme Director</p>
                </td>

                <td>
                    <div class="input-border">
                        <img src="data:image/svg+xml;base64,{{ $images['seal'] }}" alt="seal" class="seal">
                        <span class="invisible">Seal</span>
                    </div>
                    <p class="input-label">Organization Seal</p>
                </td>
            </tr>
        </table>
    </section>
</body>

</html>