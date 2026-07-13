<!-- resources/views/layouts/mail.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" />
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                @yield('content')
            </td>
        </tr>
    </table>
</body>
</html>
