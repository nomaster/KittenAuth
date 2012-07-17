<?php
/**
 * Internationalisation file for ConfirmEdit extension.
 *
 * @addtogroup Extensions
*/

$messages = array();

$messages["de"] = array(
	"kittenauth-edit"               => "Um die Seite zu speichern, klicke bitte auf das '''Kätzchen''' in den folgenden Bildern:",
	"kittenauth-desc"               => "KittenAuth-Implementierung für MediaWiki",
	"kittenauth-addurl"             => "Deine Bearbeitung enthält neue externe Verknüpfungen. Um dabei zu helfen, gegen automatisierten Spam vorzugehen, bitte bitte wähle das '''Kätzchen''' in den folgenden Bildern aus."
	"kittenauth-badlogin"           => "Um dabei zu helfen, automatisiertem Passwort-Cracking zu entgehen, wähle bitte das Kätzchen in den folgenden Bildern aus",
	"kittenauth-createaccount"      => "Um dabei zu helfen, automatisierter Kontoerstellung zu entgehen, wähle bitte das Kätzchen in den folgenden Bildern aus.",
	"kittenauth-createaccount-fail" => "Du hast entweder das falsche '''Kätzchen''' oder gar keines ausgewählt.",
	"kittenauth-create"             => "Um die Seite zu erstellen, bitte wähle das Kätzchen in den folgenden Bildern aus:",
	"captchahelp-title"             => "Captcha help",
	"kittenauth-addurl-whitelist"   => " #<!-- leave this line exactly as it is --> <pre>
# Syntax is as follows:
#   * Everything from a '#' character to the end of the line is a comment
#   * Every non-blank line is a regex fragment which will only match hosts inside URLs
 #</pre> <!-- leave this line exactly as it is -->",

	"right-skipcaptcha"          => "Captcha-auslösende Aktionen ausführen, ohne KttenAuth zu bedienen",
);

$messages["en"] = array(
	"kittenauth-edit"               => "To edit this page, please select the '''kitten''' from the images below:",
	"kittenauth-desc"               => "KittenAuth implementation for MediaWiki",
	"kittenauth-addurl"             => "Your edit includes new external links.
To help protect against automated spam, please please select the '''kitten''' from the images below:",
	"kittenauth-badlogin"           => "To help protect against automated password cracking, please select the '''kitten''' from the images below:",
	"kittenauth-createaccount"      => "To help protect against automated account creation, please select the '''kitten''' from the images below:",
	"kittenauth-createaccount-fail" => "You either picked the wrong '''kitten''' or none at all",
	"kittenauth-create"             => "To create the page, please select the '''kitten''' from the images below:",
	"captchahelp-title"          => "Captcha help",
	"kittenauth-addurl-whitelist"   => " #<!-- leave this line exactly as it is --> <pre>
# Syntax is as follows:
#   * Everything from a '#' character to the end of the line is a comment
#   * Every non-blank line is a regex fragment which will only match hosts inside URLs
 #</pre> <!-- leave this line exactly as it is -->",

	"right-skipcaptcha"          => "Perform captcha triggering actions without having to go through the KittenAuth",
);


/** Dutch (Nederlands)
 * @author Stephan Muller
 */
$messages["nl"] = array(
	"kittenauth-edit"               => "Kies een '''kitten''' uit de onderstaande plaatjes om de pagina te bewerken:",
	"kittenauth-desc"               => "KittenAuth implementatie voor MediaWiki",
	"kittenauth-addurl"             => "Je probeert een nieuwe externe link aan de pagina toe te voegen.
Kies een '''kitten''' uit de onderstaande plaatjes om de pagina te bewerken:",
	"kittenauth-badlogin"           => "Kies een '''kitten''' uit de onderstaande plaatjes om in te loggen:",
	"kittenauth-createaccount"      => "Kies een '''kitten''' uit de onderstaande plaatjes om een account te registreren:",
	"kittenauth-createaccount-fail" => "Je hebt het verkeerde plaatje gekozen",
	"kittenauth-create"             => "Kies een '''kitten''' uit de onderstaande plaatjes om de pagina aan te maken:",
	"captchahelp-title"          => "Captcha help",
	"kittenauth-addurl-whitelist"   => " #<!-- laat deze regel precies zoals hij is --> <pre>
# Syntax is as follows:
#   * Elke regel die met een # begint is commentaar
#   * Elke regel die niet leeg is, is een regex fragment dat overeenkomt met het adres in een URL
 #</pre> <!-- laat deze regel precies zoals hij is -->",

	"right-skipcaptcha"          => "Voer acties uit die gewoonlijk een KittenAuth triggeren, maar sla de KittenAuth over",
);

/** Arabic * @author Ahmad Gharbeia */
$messages["ar"] = array(
	"kittenauth-edit" => "لتحرر هذه الصفحة، رجاء اختر '''القطة''' من الصور أدناه",
	"kittenauth-desc" => "تطبيق KittenAuth لأجل ميدياويكي",
	"kittenauth-addurl" => "ما حررته يتضمن روابط خارجية. لحماية الويكي من السُّخام اختر '''القطة''' من الصور أدنا:",
	"kittenauth-badlogin" => "لحماية كسر كلمات السرِّ الآلي، اختر '''القطة''' من الصور أدناه",
	"kittenauth-createaccount" => "للحماية من إنشاء الحسابات آليا اختر '''القطة''' من الصور أدناه:",
	"kittenauth-createaccount-fail" => "إما أنك أخطأت اختيار '''القطة''' أو أنك لم تختر شيئا",
	"kittenauth-create" => "لإنشاء الصفحة اختر '''القطة''' من الصور أدناه:",
	"captchahelp-title" => "مساعدة الكابتشا",
	"kittenauth-addurl-whitelist" => " #<!-- leave this line exactly as it is --> <pre>
# Syntax is as follows:
# * Everything from a '#' character to the end of the line is a comment
# * Every non-blank line is a regex fragment which will only match hosts inside URLs
#</pre> <!-- leave this line exactly as it is -->",

	"right-skipcaptcha" => "Perform captcha triggering actions without having to go through the KittenAuth",
);
