<!DOCTYPE html>
<html>
    <head>
        <title>Pesan Baru dari Kontak Form</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6">
        <h2 style="color: #333">Anda Menerima Pesan Baru dari Website</h2>
        <p>
            <strong>Nama:</strong>
            {{ $name }}
        </p>
        <p>
            <strong>Email:</strong>
            {{ $email }}
        </p>
        <p>
            <strong>Subjek:</strong>
            {{ $subjectMessage }}
        </p>
        <hr />
        <p><strong>Isi Pesan:</strong></p>
        <p>{!! nl2br(e($bodyMessage)) !!}</p>
    </body>
</html>
