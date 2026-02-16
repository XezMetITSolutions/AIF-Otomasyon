<?php
/**
 * Toplantı PDF Raporu Oluşturma
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF için

Middleware::requireRole([Auth::ROLE_SUPER_ADMIN, Auth::ROLE_UYE]);
$auth = new Auth();
$user = $auth->getUser();

$db = Database::getInstance();
$toplanti_id = $_GET['id'] ?? null;

if (!$toplanti_id) {
    die('Toplantı ID gereklidir');
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if ($user['role'] === Auth::ROLE_UYE && $toplanti['byk_id'] != $user['byk_id']) {
    die('Erişim reddedildi: Bu toplantının raporunu görüntüleme yetkiniz yok.');
}

if (!$toplanti) {
    die('Toplantı bulunamadı');
}

// Katılımcıları getir
$katilimcilar = $db->fetchAll("
    SELECT 
        tk.*,
        k.ad,
        k.soyad,
        ab.alt_birim_adi
    FROM toplanti_katilimcilar tk
    INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.katilim_durumu, k.ad, k.soyad
", [$toplanti_id]);

// Gündem maddelerini getir
$gundem_maddeleri = $db->fetchAll("
    SELECT * FROM toplanti_gundem
    WHERE toplanti_id = ?
    ORDER BY sira_no
", [$toplanti_id]);

// Kararları getir
$kararlar = $db->fetchAll("
    SELECT 
        tk.*,
        tg.baslik as gundem_baslik
    FROM toplanti_kararlar tk
    LEFT JOIN toplanti_gundem tg ON tk.gundem_id = tg.gundem_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.karar_id
", [$toplanti_id]);

// Tutanağı getir
$tutanak = $db->fetch("
    SELECT * FROM toplanti_tutanak
    WHERE toplanti_id = ?
", [$toplanti_id]);

// PDF oluştur
// Custom PDF class for Header/Footer
class AIF_PDF extends TCPDF {
    public function Header() {
        // Logo
        $img_base64 = 'iVBORw0KGgoAAAANSUhEUgAAAs0AAAECCAYAAAAFGep9AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAC4jAAAuIwF4pT92AAAAB3RJTUUH4gMeDDsHy/LJqgAAIABJREFUeNrt3X9sHPWd//HXRJFrLjbeuKCvTUjX+BI3HAn25Qf5qhzxOkVAWqleWvWuwPfOTpp+91uBGlebIk5ftVlanYp6rHD4gu5W5BKnUqC9SpdNpZaAaLyBL5XASWo3oaVOztgNwbkvXLIhdjH+6pv5/jEzYeN4nd317O7s7vMhWdnYa3v2M+OZ13z2/fl8DNM0BQAAACC9BTQBAAAAQGgGAAAACM0AAAAAoRkAAAAgNAMAAACEZgAAAIDQDAAAABCaAQAAAEIzAAAAAEIzAAAAQGgGAAAACM0AAAAAoRkAAAAgNAMAAACEZgAAAIDQDAAAABCaAQAAABCaAQAAAEIzAAAA4JaFs33SiIUXSqqhebzBDEWTtAJQ2QzDCKT5UpP9MVMizfOTpmkO0qKe27/dafZjZV//TDNCK5TsMe2T1Jbh07N5rrI437ll1DTNUcM0zdlCc0BSP7u8aA7YB0DCDEW5uAGVEYZn/uuT1FqATRiTNGo/HpSUtD8GCdgFPQ4SktppiatCs0ErePa85QTd1MDbJqmuDF/y46ZpRhay6z1hTFLcDslxmgMo24tMm6zexDYPXVz89odmC22GYUjSkB2sB50P0zRH2atA2Z+3Us9XAfv85a/U9iA0Fz8o99GbDJTdhca5wDgXm9YSf0mt9kdnymscswN0wg7RCfY8UDbnLuejjlYhNBfTXklxepQBLjQlzumh7rTbQJIOy3nXjLIOoBTOXT5JQfu8FSQkE5oLZsEin7YsWaYVtfXafvxVaXrK+dKYpF5ZvcoM6gPK42ITTLnY+GkRSVZ5R7vdPmOyeqHjpmnSSQB459zVZJ+7ulX674IRmktJ7eJG7WhepS81366aquv0vYEXtf3oy86XD0vqpVcZKJuLTXdKUKZHZm5+SV2SugzDuCCrB5oADRTn3OX0KPcQlAnNBQ/KO2+9Q/fdcrsaa3wan0jqewMvatfwkdSwHDFD0QStBZT8xSYgq0eGty5zV5cSoMck9UnqYzAhULDzVxetQWgunKpqPdayTltuXa/l9Y2SpInpKX2j/wXCMlB+FxqffaHpEaUXbvNL2iFph2EYe+3wzDkTcPf8FZQU4fxFaC6opQ3NevrWOxRsWXfF56NHXkqtWyYsA+VxsWmyLzT0yhSG0/tsnUMJz8B8w3KP/cG7YoTmwtnaslbfX7dJjTW+Kz5/7OyI1r2yT5cmk5I1wC9ihqJ9tBhQ0hebgH2h6aQ1iqJdUj/hGSAsE5pLRVW1nly1QQ+uWH9VWJ6YntK3X9+fWorxuKxBfsyGAZR2WI6Ildi8GJ67qXkGCMuEZo+G5dDt7aqpqr7qy/1jJ7Tx0AuppRg9LEgClPTFpkmUYXg9PL9jGIbVOWGadE4AV57DumVNZUtYJjR7IyzP6F2+IKsUo5fDBijZC43TM7OD1igJOyR1G4bRTckGcPndsV4xbRyhuZDS1Sw7Tp4b14pf7nJql4ckBc1QdJRDBijZi03Qvtgwmry0+GWVbOw0TbOH5kAF3/D3infHCM2FtLShWb+668uXp42bzZ7jr2rLrw84/33cDEUjHCpASV9s+sQgv1K3ze5lC1LrjAq84e8TpRiE5oKpqtb+O4NXTR2XamJ6Sl869Lz6x96SrHKMINPIAVxs4BmtkgYNwwhSrgFu+EFozoPHVt6l/7nuvlnrlh3jE0l99pe7dPH8uGSVYwSYGQMo6YtNRNI2WqPs1Mkq19hsmmYfzYEyPYcFZC09zw0/obkwFizyaeDuh7S6oXnO5508N66WA884s2PsNUPRbg4NoGQvNm2yemcYKFPe9hiGETBNk/M1yu0cFhGDlQnNhZRJ77IkxYcHdH//T5z/bmahEqCkLzaUY1SWLsMwRHBGmZy/fLJ6l5k3ntBcIFXVOrTxAXX4V17zqSmBmfploPQvOD2SnqIlCM5ACZ6/2uzAzOw+hObCWNrQrN9t+vo1e5cl6Rv9L6TOvxxgsRKgpC84fWIqJoIzwRmlef7qFguVlIQF5fJCnlxzj/7Y+XC2gXlIUhuBGSjZi42PwIyU4NxHM6DEzmE9kvYQmEtD6fc0V1Xr6KavX3OwX5rAzAwZQAkHZkkJMeAPVwbnBLNqoETOYdzwE5oLp3Zxo/7wha1pV/UjMAMEZlScPYZhjDKPMwjMcFvJlmd0+G/Te8FHCMwAgRmYKW4YRhPNAAIzKj40b21Zq0P3bcmoflmSokdeIjAD5aOPwIxrqJM1EwFAYEblhubdn+vUcx0PZPz8+PCAth99WfpklgwCM1DaFxyWlEUmWu1FIgACMyovNO/v+Jo2r9qQ8fP7x06kzsNMYAZK+4IT4YKDLO2wlyMGCMyonNC8v+NrCrasy/j5J8+Na+OhF5z/Mg8zUNoXnG6xrCxy00cTgMCMignN2QbmiekprfjlLml6SrKWxiYwA6V7wWmTNfE/kAs/ZRoo4vkrQmAmNHs2MEvSX7z4L7o0mZSknWYo2sduBkr2guOT1VPIxP+Yjx5m00ARzl/d4h0yQrOXA3P0yEs6fXZEkg6boWgPuxgoab1ipgzMX52kCM2AAgbmNlkr/YHQ7M3A3D92wpkpY0xSkN0LlPRFJyje1oR7uhgUiAKdu5y55EFo9mZgnpieSh34F2SmDKDkLzp9tARcFqEJUAAJUVJGaC6EJ9fck3Vglqw6Znvg37cZ+AeUvD4uOsiDdnqbkecbfkrKCM2FsbVlrcJr7836+/YcfzW1jplR9kBpX3QCYgET5A9jXZCvc1dQ0jZagtCcd7WLG7Na6c8xPpHUiXlJWvS0XxcJyep0xtyaaYE5Dpmn25PMX2AsNMNc4xygywyxTV6voDk1XQvPTt97h2gadPveexKCV3C6IoWjSDEUjog6rWDcsIJDMR0+Bfk8fTZ2WnyaAxJzyc0gQmufp7qZVrmwM9cyuhedBMeinGG2OuVEvmt7hQi2qYZrmqOiYALjJz+38QU/zfHT4b1NNVbUrG/PWB+86Dwkg89dHE8BjqA/0zt9rgiYHCM1ZGqr0Bph3aP5W8yrXNuaN988Qmt26G6QmnBMJSufvtfADazg/ANzkZ6vis9m8Q7NbpRmStO+DM5J0gfmZXcNbsIVBPTPm40ARfifn2DSoZYWNcjLOG+6GZjdLMyTp9NkR7mS4K0T5YmETj/yd2nXNANKjp5nQ7G5odrM0Y3wiSdDjAC9VCZoAJXj88E4UkB4r6ZIp3A3N629a7mJoPsdOIcxxIkEloqMA8BBWL4Xrobl2caNrC5pI0tD7zJzhNnsaNBY6IfTAuy7YC45wUw14B6UZcDc0P7xkmasbcu7jj5yHo+wWlODNCcANFwAQmq92z83uhuZfWDNniJkz3GPEwj2iLgvA7Jj1BQAKEZrXNbobmk9ZPc2UErgXmH2SnqIlcjYkBkoh/wYr9HcDQMlZmMs31S5udHWqOUk6c/EcJ3Er7DYp/epgCTMUjWTyc8xQNGnEwvdL6pbUyaGesb+cWW5hxMIBSb2SWmkeuIzeXsB7qGmGe6H5b25c4vqGXJrk2pFyEU03n227EQv3mqFoRo1lhqJxSXG717lXUhfNO6ex2eqTzVA0YcTCQfsGRLKWV22SNZBqlGYDgLLC7BlwLzR/7oYl+dqerAKIHQYDKXeF8VIZlGVve1tqMLP/TRqx8GZJe9J8a9CIhQcl9UgK6pOa5b1mKNqdJjwnjViYcDeP48+utY/QRAAAEJoz1nrjzUUPzUYs3CbpNzMDpbJ8W8WIhU1ZtatJWT2HCTMUHbRDrc+tgYn29gbtkN8+y9clq6Y7obnLVNKG6Tl+d1DSDg53d2/aAAAAoXlOqxuaXd2IY9by2Vmxg+1Ved6IhSOZ1v3axlJCbGdKgJWkW+YZlH12mO1RZvWwdfY25FKDXGfEwkG7JGOmXg71jHQZsXDin3zN3c4nJsxLC7/z4dhC878/+V9pHgAACM0Zq13c6IkNtwPpbHqMWLgvix7ihGav9R3KtZfZ3rYe+6OQU771SIrP2JY2SX4O9Yzt+c5H/+c/PrNg4dTopf+74E/TkzfI1HU0CwAAlS3rKefWXl/vlW0PpPl8nbKrPU3XC9s0RzBPF5abjFi4T9bb/DtU+DmS241YOG7PwOHo5jDPzsTHE//ldx8l/X/6eHIpgRkAAOQUmr+Yv0GA2eqZ42tdM4JjWvbAwaE04XvQiIUj1wrPRiwcNGLhuKR3ZPVaF3NBkU5JCXubnPIQzM9emgAAgMqWdXnGn3ugp9le6a79Gk+LKPNe1nRTuPll9RjvMGLhA7IG6I3Kmo7Gmf0iIO+tuueXtJ/D2xVD17hBAwAAhOarfeb6Txc7MLcps5XuuuxBgaNz/KwmOxBlMuNGrgP0UJrGJPVlOagUAAAQmi0t9TcVMzD7ZA3cy5QzGG/mz8hmRgtUnscJywAAIFXWNc1uL5+dpV5lVwrRbfdMy4iFfUYsHJFVXrGHwIw5JGgCAACQKque5gWLireypF1Kke0y0HWSfmPEwjtl1TfXscuRgVGaAAAA5Byal9QWdRBgYB7fu41djUy5tQokAAAoHwu8sBEZrjDYxO5CIRixcIBWAAAAOYfme/M/3VwbuwQe0G8vUgMAAJB9aF6R//IMH7sEHtFFEwAAgJxCcz7VLm6c8+v2FGAdkr4taw5dAAAAoCAWemVDfJ+6ThevUZ5hhqIJWdOB9dqrAkbEjBgAAADIM8/0NC/71HXKJgCboWivrBk1LuTw6w6I3mrMjeMDAAB4LzR/8YYlki4vk51pcB7MMjgflnSLGYoGZc3b7DV7Je3ksPSEPpoAAAB4LjT/+Sczc2Q1GDCL4LzXDEUDzhy8dqnHgSK/7CE7KG+WtNgMRbvNULSH4Fx0QyyjDQAAUnmmpvkz13/aedimLJcxNkPRQXtu3bgkf5rA3D3L5wcldeawuRfs751pVOlXk0umfM/oNRbQSHJoFs0BefNdCAAAiqnfMIxKfe0XJLV5JjS31N/kPGzK5fvt4Nwm6231zgwCc06Byi7tQHmG5V77HQgAAABHnaQmz4Tmmqpqqapamp7KeYETMxRNSgravc7dkpJ2uYNbBgvUHH2yylS6xewg+TRkt3WcpbMBAMBcFnppY5bW36TTZ0fmvSpgytR01zLbcwKS2tM8P1CIdrADXI8RC/fKKjlp5VB11WFJEXqVAQBASYbmbUuWafvZkTojFm4qRM9funBtxMIRST26spf3grKstXYjPBuxcLek33CouuKCpG4zFI3TFPCINpoAAAjNWVt945LUC8losbbDDEUjdi/vqB2cH5dV75oswrYMGrHwkOhtdkOQ3mV4TKdhGHG5U/o1aJomN4QAUAmhecWnb3YeBmSVJRSNGYom7V7eQQ/UuzKbhjv7NK+BuVDvkKD8grNym8Xn6mPQMO4nOANABYTmxhqfFizy6dJkMuCRkOWViw+huTQEJfXSDCiiNhW5wwEAylVWi5vsPHMq7xvUbq0M2GrEwj52z2XdKv5CLCUvm9UmcxSglQEAIDQXxN8uWUYAmcEMRZP2/NAdsqZJQ24ihGYAAJD30Jz8+KO8b9B9t9xOAEkfnhNmKNpmh+fDtEhah9N8+Ow6ddfZvdjMqQ0AQJnKqqb54vnxvG+Q1+qavRqeJQXsoNYjqavCm+SCrFriuBmKDhZpG1gpEgCAMrbAixv16C2rJKuuuYldNGd4HrSXCL9F5VnzfEHXLkcZktRkhqKRIgZmQjMAAGUu69kzjp0d0eqG5rxu1D03L9MTJ15zggizEVw7PI/qk+XDAzO+7JM1or7d4y9jTNao/7isaf6umjHEfn1tsgZGOvNWB4sxf/aM7WoS82gDAEBoLrQO/0qpqlqaniI0ZxeeE0qzaqEd7Hrl0nywLtora+GYwSxeX69zg+CReZEDHH0AABCar9D/7sm89zRL0tamldo1fKSdBSNcC9Sj+qQ3eqY2WT3SM0NgPnunh2QtaT2Y4+tJe4NQBJRmAABAaL7S2xfPFWTDHmxepV3DR5xAQm+ze+F5tqCZNnza82W32UG6yf7XP8/N2Cupp9hlFW6we/A7ObIAACA0X+GlDwsTmlNKNLoJzUUN2Uk7VCfSBOmA/TjT6db22oMXywW9zAAAEJqvdvrsSME2zi7RaKVEoySCdJMdnlN7Wp0771SIrP2JY2SX4O9Yzt+c5H/+c/PrNg4dTopf+74E/TkzfI1HU0CwAAlS3rKefWXl/vlW0PpPl8nbKrPU3XC9s0RzBPF5abjFi4T9bb/DtU+DmS241YOG7PwOHo5jDPzsTHE//ldx8l/X/6eHIpgRkAAOQUmr+Yv0GA2eqZ42tdM4JjWvbAwaE04XvQiIUj1wrPRiwcNGLhuKR3ZPVaF3NBkU5JCXubnPIQzM9emgAAgMqWdXnGn3ugp9le6a79Gk+LKPNe1nRTuPll9RjvMGLhA7IG6I3Kmo7Gmf0iIO+tuueXtJ/D2xVD17hBAwAAhOarfeb6Txc7MLcps5XuuuxBgaNz/KwmOxBlMuNGrgP0UJrGJPVlOagUAAAQmi0t9TcVMzD7ZA3cy5QzGG/mz8hmRgtUnscJywAAIFXWNc1uL5+dpV5lVwrRbfdMy4iFfUYsHJFVXrGHwIw5JGgCAACQKque5gWLireypF1Kke0y0HWSfmPEwjtl1TfXscuRgVGaAAAA5Byal9QWdRBgYB7fu41djUy5tQokAAAoHwu8sBEZrjDYxO5CIRixcIBWAAAAOYfme/M/3VwbuwQe0G8vUgMAAJB9aF6R//IMH7sEHtFFEwAAgJxCcz7VLm6c8+v2FGAdkr4taw5dAAAAoCAWemVDfJ+6ThevUZ5hhqIJWdOB9dqrAkbEjBgAAADIM8/0NC/71HXKJgCboWivrBk1LuTw6w6I3mrMjeMDAAB4LzR/8YYlki4vk51pcB7MMjgflnSLGYoGZc3b7DV7Je3ksPSEPpoAAAB4LjT/+Sczc2Q1GDCL4LzXDEUDzhy8dqnHgSK/7CE7KG+WtNgMRbvNULSH4Fx0QyyjDQAAUnmmpvkz13/aedimLJcxNkPRQXtu3bgkf5rA3D3L5wcldeawuRfs751pVOlXk0umfM/oNRbQSHJoFs0BefNdCAAAiqnfMIxKfe0XJLV5JjS31N/kPGzK5fvt4Nwm6231zgwCc06Byi7tQHmG5V77HQgAAABHnaQmz4Tmmqpqqapamp7KeYETMxRNSgravc7dkpJ2uYNbBgvUHH2yylS6xewg+TRkt3WcpbMBAMBcFnppY5bW36TTZ0fmvSpgytR01zLbcwKS2tM8P1CIdrADXI8RC/fKKjlp5VB11WFJEXqVAQBASYbmbUuWafvZkTojFm4qRM9funBtxMIRST26spf3grKstXYjPBuxcLek33CouuKCpG4zFI3TFPCINpoAAAjNWVt945LUC8losbbDDEUjdi/vqB2cH5dV75Owrv/B5TVVyl/Hf1MAAAAAElFTkSuQmCC';
        $this->Image('@' . base64_decode($img_base64), 110, 8, 80, 0, 'PNG');
        
        $this->SetY(40);
        $this->SetFont('dejavusans', 'B', 14);
        $this->Cell(0, 10, 'TOPLANTI TUTANAĞI', 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer() {
        $this->SetY(-30);
        $this->SetFont('dejavusans', '', 8);
        
        // Footer Line
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);

        // Address and Contact (Left)
        $html_left = '<table border="0" cellpadding="1">
            <tr><td><strong>AİF – Avusturya İslam Federasyonu</strong> | Österreichische Islamische Föderation</td></tr>
            <tr><td>Amberggasse 10 | A-6800 Feldkirch | T +43 5522 21756 | ZVR-Zahl 777051661</td></tr>
            <tr><td>info@islamfederasyonu.at | www.islamfederasyonu.at</td></tr>
        </table>';
        
        // Bank Info (Right)
        $html_right = '<table border="0" cellpadding="1" align="right">
            <tr><td><strong>Hypo Vorarlberg</strong></td></tr>
            <tr><td>IBAN: AT87 5800 0105 6645 7011</td></tr>
            <tr><td>BIC/SWIFT: HYPVATW</td></tr>
        </table>';

        $this->writeHTMLCell(120, 20, 15, $this->GetY(), $html_left, 0, 0, false, true, 'L');
        $this->writeHTMLCell(60, 20, 135, $this->GetY(), $html_right, 0, 0, false, true, 'R');
        
        // Page number
        $this->SetY(-15);
        $this->Cell(0, 10, 'Sayfa '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// TCPDF objesini yeni sınıftan oluştur
$pdf = new AIF_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// PDF bilgileri
$pdf->SetCreator('Otomasyon Sistemi');
$pdf->SetAuthor($toplanti['olusturan']);
$pdf->SetTitle($toplanti['baslik']);
$pdf->SetSubject('Toplantı Raporu');

// Header ve Footer
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Sayfa ayarları
$pdf->SetMargins(15, 45, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(20);
$pdf->SetAutoPageBreak(TRUE, 35);

// Font
$pdf->SetFont('dejavusans', '', 10);

// Sayfa ekle
$pdf->AddPage();

// İçerik Başlığı (Header'da olduğu için burada daha küçük bir başlık atalım)
$html = '<h3 style="text-align:center; color:#6c757d;">' . htmlspecialchars($toplanti['baslik']) . '</h3>';
$html .= '<hr>';

// Toplantı Bilgileri
$html .= '<h2 style="color:#0d6efd;">Toplantı Bilgileri</h2>';
$html .= '<table border="0" cellpadding="5">';
$html .= '<tr><td width="150"><strong>BYK:</strong></td><td>' . htmlspecialchars($toplanti['byk_adi']) . '</td></tr>';
    $tarihStr = date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi']));
    if (!empty($toplanti['bitis_tarihi'])) {
        $start = new DateTime($toplanti['toplanti_tarihi']);
        $end = new DateTime($toplanti['bitis_tarihi']);
        $diff = $start->diff($end);
        
        $duration = [];
        if ($diff->h > 0) $duration[] = $diff->h . ' saat';
        if ($diff->i > 0) $duration[] = $diff->i . ' dakika';
        
        $tarihStr .= ' - ' . $end->format('H:i');
        if (!empty($duration)) {
            $tarihStr .= ' (' . implode(' ', $duration) . ')';
        }
    }
$html .= '<tr><td><strong>Tarih:</strong></td><td>' . $tarihStr . '</td></tr>';
$html .= '<tr><td><strong>Konum:</strong></td><td>' . htmlspecialchars($toplanti['konum'] ?? '-') . '</td></tr>';
$html .= '</table>';
$html .= '<br>';

// Katılımcılar
$html .= '<h2 style="color:#0d6efd;">Katılımcı Durumları</h2>';

// Katılanlar
$katilacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilacak');
if (!empty($katilacaklar)) {
    $html .= '<h3 style="color:#28a745;">Katılanlar (' . count($katilacaklar) . ')</h3>';
    $html .= '<ul>';
    foreach ($katilacaklar as $k) {
        $html .= '<li>' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']) . '</li>';
    }
    $html .= '</ul>';
}

// Katılmayacaklar
$katilmayacaklar = array_filter($katilimcilar, fn($k) => $k['katilim_durumu'] === 'katilmayacak');
if (!empty($katilmayacaklar)) {
    $html .= '<h3 style="color:#dc3545;">Katılmayacaklar (' . count($katilmayacaklar) . ')</h3>';
    $html .= '<ul>';
    foreach ($katilmayacaklar as $k) {
        $html .= '<li>' . htmlspecialchars($k['ad'] . ' ' . $k['soyad']);
         if ($k['mazeret_aciklama']) {
            $html .= ' <br><small><em>Mazeret: ' . htmlspecialchars($k['mazeret_aciklama']) . '</em></small>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
}

$html .= '<br>';

// Helper to format mentions: @Ahmet Yılmaz -> <b>Ahmet Yılmaz</b>
function formatMentions($text) {
    if (empty($text)) return '';
    // Match @Name Name (unicode supported) until punctuation or end
    // Note: Simple regex, might need refinement for complex cases
    // Explanation: @ then 1+ word characters/spaces/unicode letters, lookahead for separator
    $pattern = '/@([\w\s\p{L}]+?)(?=\s|$|<|\.|,)/u';
    $replacement = '<b>$1</b>';
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

// ... existing code ...

// Gündem
if (!empty($gundem_maddeleri)) {
    $html .= '<h2 style="color:#0d6efd;">Gündem ve Alınan Kararlar</h2>';
    $html .= '<table border="0" cellpadding="5">';
    foreach ($gundem_maddeleri as $index => $g) {
        $html .= '<tr><td>';
        $html .= '<h3>' . ($index + 1) . '. ' . htmlspecialchars($g['baslik']) . '</h3>';
        if ($g['aciklama']) {
            $html .= '<p><em>' . nl2br(htmlspecialchars($g['aciklama'])) . '</em></p>';
        }
        if (!empty($g['gorusme_notlari'])) {
            $html .= '<div style="background-color:#f8f9fa; padding:10px; border-left: 3px solid #0d6efd;">';
            $html .= '<strong>Notlar:</strong><br>';
            // Apply formatting here instead of raw htmlspecialchars
            $html .= nl2br(formatMentions($g['gorusme_notlari']));
            $html .= '</div>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><td><hr></td></tr>';
    }
    $html .= '</table>';
    $html .= '<br>';
}

// Değerlendirme
if (!empty($toplanti['baskan_degerlendirmesi'])) {
    $html .= '<h2 style="color:#0d6efd;">Bölge Başkanı Değerlendirmesi</h2>';
    $html .= '<div style="background-color:#f8f9fa; padding:15px; border: 1px solid #e9ecef; border-radius: 5px;">';
    $html .= nl2br(formatMentions($toplanti['baskan_degerlendirmesi']));
    $html .= '</div>';
    $html .= '<br>';
}



// HTML'i PDF'e yaz
$pdf->writeHTML($html, true, false, true, false, '');

// PDF çıktısı
$filename = 'Toplanti_' . date('Y-m-d', strtotime($toplanti['toplanti_tarihi'])) . '_' . $toplanti['toplanti_id'] . '.pdf';
$pdf->Output($filename, 'I'); // I = inline görüntüleme, D = indirme
