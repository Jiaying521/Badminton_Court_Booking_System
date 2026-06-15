<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
// 注释掉自动跳转，让用户每次都要手动登录
// if (isLoggedIn()) redirect('dashboard.php');

// 获取系统设置用于显示
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10');
$peak_price = getSetting('peak_price', '15');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Arena | Book Badminton Courts Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
            line-height: 1.5;
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(43,126,58,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            min-width: 280px;
            max-width: 350px;
            background: white;
            border-radius: 16px;
            padding: 1rem 1.2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 2000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast-success {
            border-left-color: #2b7e3a;
            background: #f0f9ed;
        }
        .toast-error {
            border-left-color: #e67e22;
            background: #fef5ed;
        }
        .toast i {
            font-size: 1.3rem;
        }
        .toast-success i {
            color: #2b7e3a;
        }
        .toast-error i {
            color: #e67e22;
        }
        .toast .toast-content {
            flex: 1;
            font-size: 0.85rem;
            color: #1e2a2e;
        }
        .toast .toast-close {
            cursor: pointer;
            color: #999;
            font-size: 1rem;
            transition: 0.2s;
        }
        .toast .toast-close:hover {
            color: #333;
        }
        
        /* Glassmorphism Navbar */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0.8rem 5%; 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(15px);
            position: sticky; 
            top: 0; 
            z-index: 100; 
            border-bottom: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-area { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            text-decoration: none; 
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .logo-area::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2b7e3a, #e67e22);
            transition: width 0.4s ease;
        }
        
        .logo-area:hover::after { width: 100%; }
        .logo-area:hover .logo-text { transform: scale(1.02); }
        .logo-area img { 
            height: 48px; 
            width: auto; 
            transition: transform 0.3s ease;
        }
        .logo-area:hover img { transform: scale(1.02) rotate(5deg); }
        .logo-text { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-size: 1.5rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #2b7e3a 0%, #e67e22 80%);
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
            letter-spacing: -1px;
            transition: transform 0.3s ease;
            text-transform: uppercase;
        }
        .logo-text span { 
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
        }
        
        .nav-links { 
            display: flex; 
            gap: 1rem; 
            align-items: center; 
        }
        .btn-outline { 
            background: transparent; 
            border: 2px solid #2b7e3a; 
            padding: 0.5rem 1.8rem; 
            border-radius: 50px; 
            color: #2b7e3a; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(43,126,58,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-outline:hover::before { left: 100%; }
        .btn-outline:hover { 
            background: #2b7e3a; 
            color: white; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
        }
        
        .btn-solid { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            border: none; 
            padding: 0.5rem 1.8rem; 
            border-radius: 50px; 
            color: white; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(43,126,58,0.2);
            position: relative;
            overflow: hidden;
        }
        .btn-solid::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-solid:hover::before { left: 100%; }
        .btn-solid:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(43,126,58,0.4);
        }
        
        /* Hero Section */
        .hero { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 5rem 5%; 
            gap: 4rem; 
            flex-wrap: wrap; 
            max-width: 1400px; 
            margin: 0 auto; 
            animation: fadeInUp 0.8s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hero-text { 
            flex: 1; 
            min-width: 300px; 
        }
        .hero-text .badge { 
            display: inline-block; 
            background: rgba(234,245,230,0.8);
            backdrop-filter: blur(5px);
            color: #2b7e3a; 
            padding: 0.3rem 1rem; 
            border-radius: 50px; 
            font-size: 0.8rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600; 
            margin-bottom: 1.5rem;
        }
        .hero-text h1 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 4rem; 
            font-weight: 900; 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a, #0f3d1a); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent; 
            margin-bottom: 1.2rem; 
            line-height: 1.2; 
            letter-spacing: -0.02em;
        }
        .hero-text p { 
            font-size: 1.2rem; 
            color: #4a6e4a; 
            margin-bottom: 2rem; 
            max-width: 500px; 
            line-height: 1.6;
        }
        .hero-buttons { 
            display: flex; 
            gap: 1rem; 
            flex-wrap: wrap; 
        }
        .hero-image { 
            flex: 1; 
            text-align: right; 
            position: relative;
        }
        .hero-image::before { 
            content: ''; 
            position: absolute; 
            top: -20px; 
            right: -20px; 
            width: 200px; 
            height: 200px; 
            background: radial-gradient(circle, rgba(43,126,58,0.15), transparent); 
            border-radius: 50%; 
            z-index: -1;
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .hero-image img { 
            max-width: 100%; 
            border-radius: 40px; 
            box-shadow: 0 30px 50px -20px rgba(43,126,58,0.4); 
            transition: transform 0.5s;
        }
        .hero-image img:hover { 
            transform: scale(1.02) translateY(-5px);
        }
        
        /* Statistics Bar */
        .stats-bar { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            margin: 2rem 5% 0; 
            padding: 1.5rem 2rem; 
            border-radius: 60px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            display: flex; 
            justify-content: space-around; 
            flex-wrap: wrap; 
            gap: 1.5rem;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.6s ease-out 0.2s both;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .stat-item { 
            text-align: center; 
        }
        .stat-number { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 900;
            font-size: 2rem; 
            color: #2b7e3a; 
        }
        .stat-label { 
            color: #5a6e5c; 
            font-size: 0.85rem; 
            font-weight: 500;
        }
        
        /* Features Section */
        .features { 
            padding: 5rem 5%; 
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
            border-radius: 60px 60px 0 0; 
            margin-top: 3rem;
        }
        .features h2 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            text-align: center; 
            font-size: 2.5rem; 
            color: #1e3a2a; 
            margin-bottom: 1rem;
        }
        .features-sub { 
            text-align: center; 
            color: #5a6e5c; 
            margin-bottom: 3rem; 
            font-size: 1rem;
        }
        .features-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 2rem; 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        .feature-card { 
            background: rgba(254,253,248,0.8);
            backdrop-filter: blur(5px);
            border-radius: 28px; 
            padding: 2rem; 
            text-align: center; 
            transition: all 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1); 
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .feature-card:hover { 
            transform: translateY(-12px) scale(1.02);
            border-color: rgba(43,126,58,0.3);
            box-shadow: 0 25px 45px rgba(43,126,58,0.15);
            background: white;
        }
        .feature-icon { 
            font-size: 2.5rem; 
            background: linear-gradient(145deg, #eaf5e6, #d4e8cd);
            width: 80px; 
            height: 80px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            margin: 0 auto 1.2rem; 
            color: #2b7e3a;
            transition: transform 0.3s;
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .feature-card h3 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.3rem; 
            color: #1e3a2a; 
            margin-bottom: 0.8rem;
        }
        .feature-card p { 
            color: #5a6e5c; 
            font-size: 0.9rem; 
            line-height: 1.6;
        }
        
        /* CTA Banner */
        .cta-banner { 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            margin: 2rem 5%; 
            padding: 3rem; 
            border-radius: 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 1.5rem; 
            color: white;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        .cta-banner h3 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem; 
            margin-bottom: 0.5rem;
        }
        .cta-banner p { opacity: 0.9; }
        .cta-btn { 
            background: white; 
            color: #2b7e3a; 
            border: none; 
            padding: 0.9rem 2.5rem; 
            border-radius: 50px; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.4s ease;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
            transition: left 0.5s ease;
        }
        .cta-btn:hover::before { left: 100%; }
        .cta-btn:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(8px); }
        .modal-content { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); margin: 3% auto; padding: 2rem; width: 90%; max-width: 500px; max-height: 85vh; overflow-y: auto; border-radius: 32px; animation: fadeInUp 0.4s; border: 1px solid rgba(255,255,255,0.3); }
        .modal-content::-webkit-scrollbar { width: 6px; }
        .modal-content::-webkit-scrollbar-track { background: #e0e0e0; border-radius: 3px; }
        .modal-content::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 3px; }
        .close { position: absolute; right: 1.5rem; top: 1.2rem; font-size: 1.8rem; cursor: pointer; color: #94a3b8; transition: 0.2s; }
        .close:hover { color: #2b7e3a; transform: rotate(90deg); }
        .modal-content h2 { font-family: 'Montserrat', 'Poppins', sans-serif; font-weight: 700; font-size: 1.8rem; text-align: center; margin-bottom: 1rem; background: linear-gradient(135deg, #2b7e3a, #1b5e2a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .modal-content input, .modal-content select { width: 100%; padding: 0.9rem 1rem; margin: 0.4rem 0 0.8rem; border: 2px solid rgba(224,232,220,0.8); border-radius: 60px; background: rgba(254,253,248,0.9); font-family: 'Inter', sans-serif; transition: 0.2s; }
        .modal-content input:focus, .modal-content select:focus { border-color: #2b7e3a; outline: none; box-shadow: 0 0 0 3px rgba(43,126,58,0.1); }
        .btn-primary-modal { background: linear-gradient(135deg, #2b7e3a, #1f5a2a); color: white; border: none; padding: 0.9rem; border-radius: 60px; width: 100%; font-family: 'Montserrat', 'Inter', sans-serif; font-weight: 700; cursor: pointer; margin-top: 0.5rem; transition: 0.3s; position: relative; overflow: hidden; }
        .btn-primary-modal::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s ease; }
        .btn-primary-modal:hover::before { left: 100%; }
        .btn-primary-modal:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(43,126,58,0.3); }
        .btn-secondary-modal { background: rgba(255,255,255,0.9); border: 2px solid #2b7e3a; color: #2b7e3a; padding: 0.9rem; border-radius: 60px; width: 100%; font-family: 'Montserrat', 'Inter', sans-serif; font-weight: 600; cursor: pointer; margin-top: 0.5rem; transition: 0.3s; }
        .btn-secondary-modal:hover { background: #eaf5e6; transform: translateY(-2px); }
        .hr-text { text-align: center; margin: 1rem 0; color: #94a3b8; position: relative; }
        .hr-text::before, .hr-text::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #e0e8dc; }
        .hr-text::before { left: 0; }
        .hr-text::after { right: 0; }
        .toggle-link { text-align: center; margin-top: 1rem; }
        .toggle-link a { color: #2b7e3a; text-decoration: none; font-family: 'Montserrat', sans-serif; font-weight: 600; cursor: pointer; }
        .error-msg { background: #fee2dd; border-left: 4px solid #e67e22; color: #b45f1b; padding: 0.7rem; margin-top: 1rem; border-radius: 16px; font-size: 0.85rem; display: none; }
        .success-msg { background: #d4edda; border-left: 4px solid #2b7e3a; color: #155724; padding: 0.7rem; margin-top: 1rem; border-radius: 16px; font-size: 0.85rem; display: none; }
        
        /* Password Requirements List */
        .password-requirements {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 0.6rem 1rem;
            margin-top: -0.5rem;
            margin-bottom: 0.8rem;
            font-size: 0.75rem;
        }
        .password-requirements p {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: #555;
        }
        .password-requirements ul {
            margin-left: 1.2rem;
            color: #666;
        }
        .password-requirements li {
            margin: 0.2rem 0;
        }
        .password-requirements li.valid {
            color: #2b7e3a;
            text-decoration: line-through;
            text-decoration-thickness: 1px;
            text-decoration-color: #2b7e3a;
        }
        
        .strength-meter { margin-top: -0.8rem; margin-bottom: 0.5rem; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .strength-meter-fill { height: 100%; width: 0%; transition: width 0.2s; border-radius: 3px; }
        .strength-text { font-size: 0.7rem; margin-top: 0.2rem; text-align: right; color: #5a6e5c; }
        .username-status { font-size: 0.75rem; margin-top: -0.8rem; margin-bottom: 0.5rem; }
        .username-valid { color: #2b7e3a; }
        .username-invalid { color: #e67e22; }
        .password-match { font-size: 0.75rem; margin-top: -0.5rem; margin-bottom: 0.5rem; }
        .password-match.valid { color: #2b7e3a; }
        .password-match.invalid { color: #e67e22; }
        
        /* Quick Links - disabled style */
        .footer-col a.disabled-link {
            color: #6c757d;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.6;
        }
        
        /* OTP timer */
        .otp-timer {
            font-size: 0.75rem;
            color: #e67e22;
            margin-top: 0.3rem;
            margin-bottom: 0.5rem;
            text-align: left;
            padding-left: 0.5rem;
        }
        
        /* Footer */
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 2rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .footer-col h3, .footer-col h4 { font-family: 'Montserrat', sans-serif; font-weight: 700; color: #2b7e3a; margin-bottom: 1rem; }
        .footer-col p { margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.9rem; }
        .footer-col a { color: #cbd5c0; text-decoration: none; display: block; margin-bottom: 0.6rem; transition: 0.2s; font-size: 0.9rem; cursor: pointer; }
        .footer-col a:hover { color: #2b7e3a; padding-left: 5px; transform: translateX(3px); }
        .social-icons { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-icons a { background: #2c4a2e; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.3s ease; color: #cbd5c0; text-decoration: none; }
        .social-icons a:hover { background: #2b7e3a; transform: translateY(-5px) rotate(360deg); }
        .footer-bottom { text-align: center; border-top: 1px solid #2c4a2e; padding-top: 1.5rem; font-size: 0.8rem; }
        
        @media (max-width: 768px) {
            .hero-text h1 { font-size: 2.5rem; }
            .navbar { flex-direction: column; gap: 1rem; border-radius: 28px; margin: 0 1rem; width: auto; }
            .hero { flex-direction: column; text-align: center; padding: 2rem 5%; }
            .hero-image { text-align: center; }
            .hero-text p { margin: 0 auto 1.5rem; }
            .hero-buttons { justify-content: center; }
            .stats-bar { flex-direction: column; align-items: center; border-radius: 30px; margin: 1rem 5%; }
            .features h2 { font-size: 2rem; }
            .cta-banner { text-align: center; justify-content: center; margin: 1rem 5%; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="homepage.php" class="logo-area">
        <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
        <div class="logo-text">Smash <span>Arena</span></div>
    </a>
    <div class="nav-links">
        <button class="btn-outline" id="loginBtn">Login</button>
        <button class="btn-solid" id="signupBtn">Sign Up</button>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-text">
        <span class="badge"><i class="fas fa-shuttlecock"></i> Smash Arena</span>
        <h1>Smash & Play<br>Book Courts Instantly</h1>
        <p>Professional badminton courts, flexible hours, and secure online booking. Play your best game today.</p>
        <div class="hero-buttons">
            <button class="btn-solid" id="heroBookBtn" style="padding:0.8rem 2rem; font-size:1rem;"><i class="fas fa-calendar-check"></i> Book Now →</button>
            <button class="btn-outline" onclick="scrollToFeatures()"><i class="fas fa-arrow-down"></i> Learn More</button>
        </div>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600" alt="Badminton court">
    </div>
</section>

<!-- Statistics Bar - 从数据库读取数据 -->
<div class="stats-bar">
    <div class="stat-item"><div class="stat-number">10+</div><div class="stat-label">Premium Courts</div></div>
    <div class="stat-item"><div class="stat-number"><?php echo date('g:i A', strtotime($open_time)); ?> - <?php echo date('g:i A', strtotime($close_time)); ?></div><div class="stat-label">Daily Operation</div></div>
    <div class="stat-item"><div class="stat-number">RM<?php echo $off_peak_price; ?>-<?php echo $peak_price; ?></div><div class="stat-label">Per Hour</div></div>
    <div class="stat-item"><div class="stat-number">24/7</div><div class="stat-label">Online Booking</div></div>
</div>

<!-- Features Section -->
<section class="features" id="features">
    <h2>Why Choose Smash Arena?</h2>
    <div class="features-sub">Experience the best badminton facilities in Malaysia</div>
    <div class="features-grid">
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-calendar-check"></i></div><h3>Easy Booking</h3><p>Select court, pick time, pay online – done in under a minute. No phone calls needed.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-clock"></i></div><h3>Extended Hours</h3><p>Open daily <?php echo date('g:i A', strtotime($open_time)); ?> - <?php echo date('g:i A', strtotime($close_time)); ?>. Early bird and late night sessions available.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-chalkboard-user"></i></div><h3>Training Courts</h3><p>Professional coaches available for all levels. Improve your game with expert guidance.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><h3>Secure Payments</h3><p>Multiple payment options with full encryption. Your transactions are 100% secure.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-wallet"></i></div><h3>Wallet System</h3><p>Easy top-up and instant refunds to your digital wallet. Manage your funds easily.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-star"></i></div><h3>Reward Points</h3><p>Earn points with every booking and redeem them for discounts on future sessions.</p></div>
    </div>
</section>

<!-- CTA Banner -->
<div class="cta-banner">
    <div><h3>Ready to Play?</h3><p>Book your court now and enjoy 10% off your first booking!</p></div>
    <button class="cta-btn" id="ctaBookBtn">Book Now <i class="fas fa-arrow-right"></i></button>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col"><h3>Smash Arena</h3>
        <p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, Kuala Lumpur</p>
        <p><i class="fas fa-phone-alt"></i> +603-1234 5678</p>
        <p><i class="fas fa-envelope"></i> smasharenabadminton@gmail.com</p>
        <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
    </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="#" class="requires-login" data-href="dashboard.php">Find a Court</a>
            <a href="#" class="requires-login" data-href="my_bookings.php">My Booking</a>
            <a href="#" class="requires-login" data-href="../Payment_Module/wallet.php">Wallet</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="faq.php">FAQs</a>
            <a href="cancellation_policy.php">Cancellation Policy</a>
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_use.php">Terms of Use</a>
            <a href="contact_us.php">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Operating Hours</h4>
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo date('g:i A', strtotime($open_time)); ?> - <?php echo date('g:i A', strtotime($close_time)); ?></p>
            <p><i class="fas fa-tag"></i> <?php echo date('g:i A', strtotime($open_time)); ?> - <?php echo date('g:i A', strtotime($peak_start)); ?>: RM <?php echo $off_peak_price; ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo date('g:i A', strtotime($peak_start)); ?> - <?php echo date('g:i A', strtotime($close_time)); ?>: RM <?php echo $peak_price; ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom"><p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p></div>
</footer>

<!-- Login Modal -->
<div id="loginModal" class="modal"><div class="modal-content"><span class="close" id="closeLogin">&times;</span><h2>Welcome Back</h2>
<div id="loginPasswordMode">
    <input type="email" id="loginEmail" placeholder="Email address">
    <div style="position: relative;">
        <input type="password" id="loginPassword" placeholder="Password" style="padding-right: 45px;">
        <i class="fas fa-eye-slash" id="toggleLoginPassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 1.1rem;"></i>
    </div>
    <button class="btn-primary-modal" id="doPasswordLogin">Login with Password</button>
</div>
<div class="hr-text">———— OR ————</div>
<div id="loginOtpMode"><input type="email" id="loginOtpEmail" placeholder="Email address"><button class="btn-secondary-modal" id="sendLoginOtpBtn">Send OTP Code</button><div id="loginOtpTimer" class="otp-timer" style="display:none;"></div><input type="text" id="loginOtpCode" placeholder="Enter 6-digit OTP" style="display:none;"><button class="btn-primary-modal" id="verifyLoginOtpBtn" style="display:none;">Verify & Login</button></div>
<div class="toggle-link"><a id="forgotPasswordLink">Forgot Password?</a><span style="margin:0 10px;">|</span><a id="switchToRegisterFromLogin">No account? Sign up</a></div>
<div id="loginError" class="error-msg"></div></div></div>

<!-- Forgot Password Modal -->
<div id="forgotPasswordModal" class="modal"><div class="modal-content"><span class="close" id="closeForgotPassword">&times;</span><h2>Reset Password</h2>
<div id="forgotStep1"><label>Email Address</label><input type="email" id="forgotEmail" placeholder="Enter your registered email"><button class="btn-primary-modal" id="sendResetOtpBtn">Send Reset Code</button><div id="forgotOtpTimer" class="otp-timer" style="display:none;"></div></div>
<div id="forgotStep2" style="display:none;"><label>Verification Code</label><input type="text" id="forgotOtpCode" placeholder="Enter 6-digit code"><label>New Password</label>
<div style="position: relative;">
    <input type="password" id="forgotNewPassword" placeholder="At least 6 characters + 1 symbol" style="padding-right: 45px;">
    <i class="fas fa-eye-slash" id="toggleForgotPassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 1.1rem;"></i>
</div>
<div class="strength-meter"><div class="strength-meter-fill" id="forgotStrengthFill"></div></div><div id="forgotStrengthText" class="strength-text"></div><label>Confirm Password</label>
<div style="position: relative;">
    <input type="password" id="forgotConfirmPassword" placeholder="Confirm your new password" style="padding-right: 45px;">
    <i class="fas fa-eye-slash" id="toggleForgotConfirmPassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 1.1rem;"></i>
</div>
<div id="forgotPasswordMatch" class="password-match"></div><button class="btn-primary-modal" id="resetPasswordBtn">Reset Password</button></div>
<div id="forgotError" class="error-msg"></div><div id="forgotSuccess" class="success-msg"></div></div></div>

<!-- Register Modal -->
<div id="registerModal" class="modal"><div class="modal-content"><span class="close" id="closeRegister">&times;</span><h2>Create Account</h2>

<label>Name <span style="color:#e67e22;">*</span></label>
<input type="text" id="regName" placeholder="Your display name"><div id="nameStatus" class="username-status"></div>

<label>Phone <span style="color:#e67e22;">*</span></label>
<div style="display:flex; gap:8px;"><select id="regPhoneCode" style="width:30%;"><option value="+60">+60 (MY)</option><option value="+65">+65 (SG)</option></select><input type="tel" id="regPhone" placeholder="12345678" style="width:70%;"></div>

<label>Email <span style="color:#e67e22;">*</span></label>
<input type="email" id="regEmail" placeholder="Your email">

<label>Password <span style="color:#e67e22;">*</span></label>
<div style="position: relative;">
    <input type="password" id="regPassword" placeholder="At least 6 characters + 1 symbol" style="padding-right: 45px;">
    <i class="fas fa-eye-slash" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 1.1rem;"></i>
</div>
<div class="strength-meter"><div class="strength-meter-fill" id="passwordStrengthFill"></div></div>
<div id="passwordStrengthText" class="strength-text"></div>

<!-- Password Requirements List -->
<div id="passwordRequirementsList" class="password-requirements" style="display: none;">
    <p>📋 Password must meet:</p>
    <ul>
        <li id="reqLength">✗ At least 6 characters</li>
        <li id="reqSymbol">✗ At least 1 symbol (!@#$%^&*)</li>
        <li id="reqLetter">✗ Letters (A-Z, a-z)</li>
        <li id="reqNumber">✗ Numbers (0-9)</li>
    </ul>
</div>

<label>Confirm Password <span style="color:#e67e22;">*</span></label>
<div style="position: relative;">
    <input type="password" id="regConfirmPassword" placeholder="Confirm your password" style="padding-right: 45px;">
    <i class="fas fa-eye-slash" id="toggleConfirmPassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 1.1rem;"></i>
</div>
<div id="passwordMatch" class="password-match"></div>

<!-- OTP Section -->
<button class="btn-secondary-modal" id="sendRegCodeBtn" disabled style="opacity:0.6; cursor:not-allowed;">Send Verification Code</button>
<div id="regOtpTimer" class="otp-timer" style="display:none;"></div>
<input type="text" id="regVerifyCode" placeholder="Enter 6-digit verification code" style="display:none;">

<!-- 验证提示框 -->
<div style="background: #fff3cd; border-left: 4px solid #e67e22; padding: 0.6rem 1rem; margin: 0.8rem 0; border-radius: 12px; font-size: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
    <i class="fas fa-info-circle" style="color: #e67e22; font-size: 1rem;"></i>
    <span style="color: #856404;"><strong>⚠️ Note:</strong> After entering the verification code, click <strong>"Verify & Register"</strong> to complete registration.</span>
</div>

<button class="btn-primary-modal" id="registerFinalBtn" disabled>Verify & Register</button>
<div class="toggle-link"><a id="switchToLoginFromRegister">Already have an account? Log in</a></div>
<div id="regError" class="error-msg"></div></div></div>

<!-- Toast Container -->
<div id="toastContainer"></div>

<script>
    const baseUrl = './';
    let otpCooldown = false;
    let otpTimerInterval = null;
    let forgotOtpCooldown = false;
    let forgotTimerInterval = null;
    let regOtpCooldown = false;
    let regTimerInterval = null;
    
    // Toast function
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <div class="toast-content">${message}</div>
            <i class="fas fa-times toast-close"></i>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.onclick = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        };
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }
    
    function scrollToFeatures() { document.getElementById('features').scrollIntoView({ behavior: 'smooth' }); }
    
    // 密码显示/隐藏切换功能
    function setupPasswordToggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input && icon) {
            icon.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }
    }
    
    // Quick links - require login
    document.querySelectorAll('.requires-login').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            showToast('Please login to access this feature', 'error');
            openLogin();
        });
    });
    
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    function openLogin() { loginModal.style.display = 'block'; }
    function openRegister() { registerModal.style.display = 'block'; }
    function openForgotPassword() { forgotPasswordModal.style.display = 'block'; document.getElementById('forgotStep1').style.display = 'block'; document.getElementById('forgotStep2').style.display = 'none'; document.getElementById('forgotEmail').value = ''; document.getElementById('forgotOtpCode').value = ''; document.getElementById('forgotNewPassword').value = ''; document.getElementById('forgotConfirmPassword').value = ''; document.getElementById('forgotError').style.display = 'none'; document.getElementById('forgotSuccess').style.display = 'none'; }
    function closeAll() { loginModal.style.display = 'none'; registerModal.style.display = 'none'; forgotPasswordModal.style.display = 'none'; }
    document.getElementById('loginBtn').onclick = openLogin;
    document.getElementById('signupBtn').onclick = openRegister;
    document.getElementById('heroBookBtn').onclick = openLogin;
    document.getElementById('ctaBookBtn').onclick = openLogin;
    document.getElementById('closeLogin').onclick = () => loginModal.style.display = 'none';
    document.getElementById('closeRegister').onclick = () => registerModal.style.display = 'none';
    document.getElementById('closeForgotPassword').onclick = () => forgotPasswordModal.style.display = 'none';
    document.getElementById('forgotPasswordLink').onclick = (e) => { e.preventDefault(); closeAll(); openForgotPassword(); };
    window.onclick = (e) => { if(e.target === loginModal) loginModal.style.display = 'none'; if(e.target === registerModal) registerModal.style.display = 'none'; if(e.target === forgotPasswordModal) forgotPasswordModal.style.display = 'none'; };
    document.getElementById('switchToRegisterFromLogin').onclick = (e) => { e.preventDefault(); closeAll(); openRegister(); };
    document.getElementById('switchToLoginFromRegister').onclick = (e) => { e.preventDefault(); closeAll(); openLogin(); };

    function setButtonLoading(btn, isLoading) { if(!btn) return; if(isLoading) { btn._orig = btn.innerText; btn.innerText = btn.getAttribute('data-loading')||'Processing...'; btn.disabled=true; } else { btn.innerText = btn._orig; btn.disabled=false; } }

    // Password requirements checking
    function checkPasswordRequirements(pwd) {
        const hasLength = pwd.length >= 6;
        const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
        const hasLetter = /[A-Za-z]/.test(pwd);
        const hasNumber = /\d/.test(pwd);
        return { hasLength, hasSymbol, hasLetter, hasNumber };
    }
    
    function updatePasswordRequirements(pwd) {
        const reqList = document.getElementById('passwordRequirementsList');
        if (!reqList) return;
        if (pwd.length === 0) {
            reqList.style.display = 'none';
            return;
        }
        reqList.style.display = 'block';
        const reqs = checkPasswordRequirements(pwd);
        const lengthEl = document.getElementById('reqLength');
        const symbolEl = document.getElementById('reqSymbol');
        const letterEl = document.getElementById('reqLetter');
        const numberEl = document.getElementById('reqNumber');
        if (lengthEl) lengthEl.innerHTML = reqs.hasLength ? '✓ At least 6 characters' : '✗ At least 6 characters';
        if (symbolEl) symbolEl.innerHTML = reqs.hasSymbol ? '✓ At least 1 symbol (!@#$%^&*)' : '✗ At least 1 symbol (!@#$%^&*)';
        if (letterEl) letterEl.innerHTML = reqs.hasLetter ? '✓ Letters (A-Z, a-z)' : '✗ Letters (A-Z, a-z)';
        if (numberEl) numberEl.innerHTML = reqs.hasNumber ? '✓ Numbers (0-9)' : '✗ Numbers (0-9)';
    }

    // DOM elements
    const regNameInput = document.getElementById('regName');
    const regEmail = document.getElementById('regEmail');
    const regPhone = document.getElementById('regPhone');
    const regPhoneCode = document.getElementById('regPhoneCode');
    const regPhoneInput = regPhone;
    const regPasswordInput = document.getElementById('regPassword');
    const regConfirmPassword = document.getElementById('regConfirmPassword');
    const sendRegCodeBtn = document.getElementById('sendRegCodeBtn');
    const regVerifyCode = document.getElementById('regVerifyCode');
    const registerFinalBtn = document.getElementById('registerFinalBtn');
    
    const passwordMatchDiv = document.getElementById('passwordMatch');
    const nameStatusDiv = document.getElementById('nameStatus');
    const strengthFill = document.getElementById('passwordStrengthFill');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let nameValid = false;
    let passwordValid = false;
    let passwordMatchValid = false;
    let regEmailStored = '';
    
    // 设置密码眼睛
    setupPasswordToggle('regPassword', 'togglePassword');
    setupPasswordToggle('regConfirmPassword', 'toggleConfirmPassword');
    setupPasswordToggle('loginPassword', 'toggleLoginPassword');
    setupPasswordToggle('forgotNewPassword', 'toggleForgotPassword');
    setupPasswordToggle('forgotConfirmPassword', 'toggleForgotConfirmPassword');
    
    // Validate name
    regNameInput.addEventListener('blur', async function() { 
        const name = this.value.trim(); 
        if(name.length < 2) { 
            nameStatusDiv.innerHTML = '<span class="username-invalid">Name must be at least 2 characters</span>'; 
            nameValid = false; 
            validateSendCodeButton();
            return; 
        } 
        try { 
            const res = await fetch(baseUrl + 'check_username.php?name=' + encodeURIComponent(name)); 
            const data = await res.json(); 
            if(data.exists) { 
                nameStatusDiv.innerHTML = '<span class="username-invalid">❌ This name is already taken</span>'; 
                nameValid = false; 
            } else { 
                nameStatusDiv.innerHTML = '<span class="username-valid">✓ Name available</span>'; 
                nameValid = true; 
            } 
        } catch(e) { 
            nameStatusDiv.innerHTML = '<span class="username-invalid">Error checking name</span>'; 
            nameValid = false; 
        } 
        validateSendCodeButton(); 
    });
    
    // Password strength check
    function checkPasswordStrength(pwd) { 
        const reqs = checkPasswordRequirements(pwd);
        let score = 0;
        if(reqs.hasLength) score++;
        if(reqs.hasLetter) score++;
        if(reqs.hasNumber) score++;
        if(reqs.hasSymbol) score++;
        if(pwd.length >= 8) score++;
        let percent = 0, text = '', valid = false;
        if(pwd.length === 0) { percent = 0; text = ''; valid = false; }
        else if(score <= 2) { percent = 25; text = 'Weak'; valid = false; }
        else if(score === 3) { percent = 50; text = 'Fair'; valid = false; }
        else if(score === 4) { percent = 75; text = 'Good'; valid = true; }
        else { percent = 100; text = 'Strong'; valid = true; }
        const isValid = reqs.hasLength && reqs.hasSymbol && reqs.hasLetter && reqs.hasNumber;
        if(pwd.length < 6) { text = 'Too short (min 6)'; valid = false; }
        else if(!reqs.hasSymbol) { text = 'Need at least 1 symbol (!@#$...)'; valid = false; }
        else if(!reqs.hasLetter) { text = 'Need letters'; valid = false; }
        else if(!reqs.hasNumber) { text = 'Need numbers'; valid = false; }
        return { percent, text, valid }; 
    }
    
    regPasswordInput.addEventListener('input', function() { 
        const pwd = this.value; 
        const result = checkPasswordStrength(pwd); 
        strengthFill.style.width = result.percent + '%'; 
        if(result.percent <= 25) strengthFill.style.background = '#e67e22'; 
        else if(result.percent <= 50) strengthFill.style.background = '#f1c40f'; 
        else if(result.percent <= 75) strengthFill.style.background = '#2b7e3a'; 
        else strengthFill.style.background = '#2b7e3a'; 
        strengthText.innerText = result.text; 
        passwordValid = result.valid; 
        updatePasswordRequirements(pwd);
        validateSendCodeButton(); 
        validatePasswordMatch(); 
    });
    
    // Validate password match
    function validatePasswordMatch() { 
        const password = regPasswordInput.value; 
        const confirm = regConfirmPassword.value; 
        if(confirm.length === 0) { 
            passwordMatchDiv.innerHTML = ''; 
            passwordMatchValid = false; 
        } else if(password === confirm) { 
            passwordMatchDiv.innerHTML = '<span class="valid">✓ Passwords match</span>'; 
            passwordMatchValid = true; 
        } else { 
            passwordMatchDiv.innerHTML = '<span class="invalid">✗ Passwords do not match</span>'; 
            passwordMatchValid = false; 
        } 
        validateSendCodeButton(); 
    }
    regConfirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Validate send code button
    function validateSendCodeButton() {
        const nameOk = nameValid;
        const emailOk = regEmail.value.trim().includes('@');
        const phoneOk = regPhone.value.trim().length > 5;
        const passwordOk = passwordValid && passwordMatchValid;
        
        if(nameOk && emailOk && phoneOk && passwordOk) {
            sendRegCodeBtn.disabled = false;
            sendRegCodeBtn.style.opacity = '1';
            sendRegCodeBtn.style.cursor = 'pointer';
        } else {
            sendRegCodeBtn.disabled = true;
            sendRegCodeBtn.style.opacity = '0.6';
            sendRegCodeBtn.style.cursor = 'not-allowed';
        }
    }
    
    // Input event listeners
    regEmail.addEventListener('input', validateSendCodeButton);
    regPhone.addEventListener('input', validateSendCodeButton);
    regNameInput.addEventListener('input', validateSendCodeButton);
    regPasswordInput.addEventListener('input', validateSendCodeButton);
    regConfirmPassword.addEventListener('input', validateSendCodeButton);
    
    // OTP timer function
    function startOtpTimer(timerElementId, seconds = 60, onExpire) {
        let remaining = seconds;
        const timerElement = document.getElementById(timerElementId);
        if (timerElement) {
            timerElement.style.display = 'block';
            timerElement.innerHTML = `⏱️ Resend available in ${remaining}s`;
            const interval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    timerElement.style.display = 'none';
                    if (onExpire) onExpire();
                } else {
                    timerElement.innerHTML = `⏱️ Resend available in ${remaining}s`;
                }
            }, 1000);
            return interval;
        }
        return null;
    }
    
    // Send OTP
    sendRegCodeBtn.onclick = async () => {
        if (regOtpCooldown) {
            showToast('Please wait 60 seconds before requesting another OTP', 'error');
            return;
        }
        
        const name = regNameInput.value.trim();
        const email = regEmail.value.trim();
        const password = regPasswordInput.value;
        
        if(!nameValid) { showToast("Please choose a valid unique name.", 'error'); return; }
        if(!passwordValid) { showToast("Password does not meet requirements.", 'error'); return; }
        if(!name || !email || !password || !regPhone.value.trim()) { showToast("Please fill all fields.", 'error'); return; }
        
        setButtonLoading(sendRegCodeBtn, true);
        try {
            const res = await fetch(baseUrl+'send_otp.php', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body:JSON.stringify({ email: email, type:'register' }) 
            });
            const data = await res.json();
            if(data.success) {
                regEmailStored = email;
                showToast("✓ Verification code sent to your email!", 'success');
                regVerifyCode.style.display = 'block';
                registerFinalBtn.disabled = false;
                regOtpCooldown = true;
                if (regTimerInterval) clearInterval(regTimerInterval);
                regTimerInterval = startOtpTimer('regOtpTimer', 60, () => { regOtpCooldown = false; });
            } else { 
                showToast(data.message, 'error'); 
            }
        } catch(err) { 
            showToast("Network error: " + err.message, 'error'); 
        }
        finally { 
            setButtonLoading(sendRegCodeBtn, false); 
        }
    };
    
    // Verify & Register combined
    registerFinalBtn.onclick = async () => { 
        const code = regVerifyCode.value.trim();
        const email = regEmailStored || regEmail.value.trim();
        
        if(!email || !code) { 
            showToast("Please enter the verification code sent to your email.", 'error'); 
            return; 
        }
        
        setButtonLoading(registerFinalBtn, true);
        
        try { 
            // Verify OTP
            const verifyRes = await fetch(baseUrl+'verify_otp.php', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body:JSON.stringify({ email, code, type:'register' }) 
            });
            const verifyData = await verifyRes.json();
            
            if(!verifyData.success) { 
                showToast(verifyData.message, 'error'); 
                setButtonLoading(registerFinalBtn, false);
                return; 
            }
            
            // Register
            const name = regNameInput.value.trim(); 
            const password = regPasswordInput.value; 
            const phoneFull = regPhoneCode.value + regPhone.value.trim(); 
            
            const regRes = await fetch(baseUrl+'register.php', { 
                method:'POST', 
                headers:{'Content-Type':'application/json'}, 
                body:JSON.stringify({ name, email, password, phone:phoneFull }) 
            });
            const regData = await regRes.json();
            
            if(regData.success) { 
                showToast("✓ Registration successful! Please login.", 'success'); 
                setTimeout(() => { 
                    closeAll(); 
                    openLogin(); 
                }, 1500);
                
                // Reset form
                regNameInput.value = ''; 
                regEmail.value = ''; 
                regPasswordInput.value = ''; 
                regConfirmPassword.value = ''; 
                regPhone.value = ''; 
                regVerifyCode.value = ''; 
                regVerifyCode.style.display = 'none';
                registerFinalBtn.disabled = true; 
                nameValid = false; 
                passwordValid = false; 
                passwordMatchValid = false; 
                sendRegCodeBtn.disabled = true;
                sendRegCodeBtn.style.opacity = '0.6';
            } else { 
                showToast(regData.message, 'error'); 
            } 
        } catch(err) { 
            showToast("Registration failed. Please try again.", 'error'); 
        } 
        finally { 
            setButtonLoading(registerFinalBtn, false); 
        } 
    };
    
    // Forgot password handlers
    const forgotNewPassword = document.getElementById('forgotNewPassword');
    const forgotConfirmPassword = document.getElementById('forgotConfirmPassword');
    const forgotStrengthFill = document.getElementById('forgotStrengthFill');
    const forgotStrengthText = document.getElementById('forgotStrengthText');
    const forgotPasswordMatchDiv = document.getElementById('forgotPasswordMatch');
    
    if(forgotNewPassword) { 
        forgotNewPassword.addEventListener('input', function() { 
            const pwd = this.value; 
            const result = checkPasswordStrength(pwd); 
            forgotStrengthFill.style.width = result.percent + '%'; 
            if(result.percent <= 25) forgotStrengthFill.style.background = '#e67e22'; 
            else if(result.percent <= 50) forgotStrengthFill.style.background = '#f1c40f'; 
            else if(result.percent <= 75) forgotStrengthFill.style.background = '#2b7e3a'; 
            else forgotStrengthFill.style.background = '#2b7e3a'; 
            forgotStrengthText.innerText = result.text; 
            if(forgotConfirmPassword.value.length > 0) { 
                if(pwd === forgotConfirmPassword.value) { 
                    forgotPasswordMatchDiv.innerHTML = '<span class="valid">✓ Passwords match</span>'; 
                } else { 
                    forgotPasswordMatchDiv.innerHTML = '<span class="invalid">✗ Passwords do not match</span>'; 
                } 
            } 
        }); 
        forgotConfirmPassword.addEventListener('input', function() { 
            if(this.value.length > 0 && this.value === forgotNewPassword.value) { 
                forgotPasswordMatchDiv.innerHTML = '<span class="valid">✓ Passwords match</span>'; 
            } else if(this.value.length > 0) { 
                forgotPasswordMatchDiv.innerHTML = '<span class="invalid">✗ Passwords do not match</span>'; 
            } else { 
                forgotPasswordMatchDiv.innerHTML = ''; 
            } 
        }); 
    }
    
    // Login handlers
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');
    const doPasswordLogin = document.getElementById('doPasswordLogin');
    
    doPasswordLogin.onclick = async () => { 
        const email = loginEmail.value.trim(); 
        const password = loginPassword.value; 
        if(!email || !password) { showToast("Enter email and password.", 'error'); return; } 
        doPasswordLogin.disabled = true; 
        doPasswordLogin.innerText = "Logging in..."; 
        try { 
            const res = await fetch(baseUrl+'login_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, password }) }); 
            const data = await res.json(); 
            if(data.success) { 
                window.location.href = baseUrl+'dashboard.php'; 
            } else { 
                showToast(data.message, 'error'); 
            } 
        } catch(err) { 
            showToast("Login failed.", 'error'); 
        } finally { 
            doPasswordLogin.disabled = false; 
            doPasswordLogin.innerText = "Login with Password"; 
        } 
    };
    
    const loginOtpEmail = document.getElementById('loginOtpEmail');
    const sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn');
    const loginOtpCode = document.getElementById('loginOtpCode');
    const verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');
    
    sendLoginOtpBtn.onclick = async () => { 
        if (otpCooldown) { showToast('Please wait 60 seconds before requesting another OTP', 'error'); return; }
        const email = loginOtpEmail.value.trim(); 
        if(!email) { showToast("Enter email.", 'error'); return; } 
        setButtonLoading(sendLoginOtpBtn, true); 
        try { 
            const res = await fetch(baseUrl+'send_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, type:'login' }) }); 
            const data = await res.json(); 
            if(data.success) { 
                loginOtpCode.style.display = 'block'; 
                verifyLoginOtpBtn.style.display = 'block'; 
                showToast("✓ OTP sent to your email!", 'success'); 
                otpCooldown = true;
                if (otpTimerInterval) clearInterval(otpTimerInterval);
                otpTimerInterval = startOtpTimer('loginOtpTimer', 60, () => { otpCooldown = false; });
            } else { showToast(data.message, 'error'); } 
        } catch(err) { showToast("Failed to send OTP.", 'error'); } 
        finally { setButtonLoading(sendLoginOtpBtn, false); } 
    };
    
    verifyLoginOtpBtn.onclick = async () => { 
        const email = loginOtpEmail.value.trim(); 
        const code = loginOtpCode.value.trim(); 
        if(!email || !code) { showToast("Enter OTP.", 'error'); return; } 
        setButtonLoading(verifyLoginOtpBtn, true); 
        try { 
            const verifyRes = await fetch(baseUrl+'verify_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, code, type:'login' }) }); 
            const verifyData = await verifyRes.json(); 
            if(!verifyData.success) { showToast(verifyData.message, 'error'); return; } 
            const loginRes = await fetch(baseUrl+'login_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email }) }); 
            const loginData = await loginRes.json(); 
            if(loginData.success) { 
                window.location.href = baseUrl+'dashboard.php'; 
            } else { 
                showToast(loginData.message, 'error'); 
            } 
        } catch(err) { showToast("OTP login failed.", 'error'); } 
        finally { setButtonLoading(verifyLoginOtpBtn, false); } 
    };
    
    const sendResetOtpBtn = document.getElementById('sendResetOtpBtn');
    if(sendResetOtpBtn) { 
        sendResetOtpBtn.onclick = async () => { 
            if (forgotOtpCooldown) { showToast('Please wait 60 seconds before requesting another OTP', 'error'); return; }
            const email = document.getElementById('forgotEmail').value.trim(); 
            if(!email) { showToast("Please enter your email", 'error'); return; } 
            setButtonLoading(sendResetOtpBtn, true); 
            try { 
                const res = await fetch(baseUrl+'send_reset_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: email }) }); 
                const data = await res.json(); 
                if(data.success) { 
                    document.getElementById('forgotStep1').style.display = 'none'; 
                    document.getElementById('forgotStep2').style.display = 'block'; 
                    showToast("Reset code sent to your email!", 'success'); 
                    forgotOtpCooldown = true;
                    if (forgotTimerInterval) clearInterval(forgotTimerInterval);
                    forgotTimerInterval = startOtpTimer('forgotOtpTimer', 60, () => { forgotOtpCooldown = false; });
                } else { showToast(data.message, 'error'); } 
            } catch(err) { showToast("Network error", 'error'); } 
            finally { setButtonLoading(sendResetOtpBtn, false); } 
        }; 
    }
    
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    if(resetPasswordBtn) { 
        resetPasswordBtn.onclick = async () => { 
            const email = document.getElementById('forgotEmail').value.trim(); 
            const code = document.getElementById('forgotOtpCode').value.trim(); 
            const newPassword = document.getElementById('forgotNewPassword').value; 
            const confirmPassword = document.getElementById('forgotConfirmPassword').value; 
            if(!code) { showToast("Please enter verification code", 'error'); return; } 
            if(newPassword !== confirmPassword) { showToast("Passwords do not match", 'error'); return; } 
            const pwdResult = checkPasswordStrength(newPassword); 
            if(!pwdResult.valid) { showToast(pwdResult.text, 'error'); return; } 
            setButtonLoading(resetPasswordBtn, true); 
            try { 
                const res = await fetch(baseUrl+'reset_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, code, new_password: newPassword }) }); 
                const data = await res.json(); 
                if(data.success) { 
                    showToast("Password reset successful! Please login.", 'success'); 
                    setTimeout(() => { forgotPasswordModal.style.display = 'none'; openLogin(); }, 2000); 
                } else { showToast(data.message, 'error'); } 
            } catch(err) { showToast("Network error", 'error'); } 
            finally { setButtonLoading(resetPasswordBtn, false); } 
        }; 
    }
    
    // Initial validation
    setTimeout(() => {
        validateSendCodeButton();
    }, 100);
    
    updatePasswordRequirements('');
</script>
</body>
</html>