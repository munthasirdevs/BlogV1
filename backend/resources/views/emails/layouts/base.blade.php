<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Newsletter')</title>
    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        
        /* Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .header {
            background-color: #1a1a2e;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }
        
        .content h2 {
            color: #1a1a2e;
            font-size: 20px;
            margin-top: 0;
        }
        
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .button:hover {
            background-color: #4338ca;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            font-size: 13px;
            color: #666666;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin: 15px 0;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #666666;
        }
        
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 25px 0;
        }
        
        /* Mobile responsive */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .content {
                padding: 30px 20px !important;
            }
            .header h1 {
                font-size: 20px !important;
            }
            .content h2 {
                font-size: 18px !important;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" class="email-container" style="border-collapse: collapse;">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h1>@yield('header', config('app.name'))</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content">
                            @yield('content')
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            @yield('footer_content')
                            
                            <div class="social-links">
                                @yield('social_links')
                            </div>
                            
                            <p style="margin: 10px 0;">
                                @yield('footer_text', '&copy; ' . date('Y') . ' ' . config('app.name') . '. All rights reserved.')
                            </p>
                            
                            <p style="margin: 10px 0;">
                                @yield('unsubscribe_link')
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Tracking pixel -->
    @yield('tracking_pixel')
</body>
</html>
