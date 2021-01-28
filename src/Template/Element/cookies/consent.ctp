<?php
use Cake\Utility\Hash;

// doc: https://2gdpr.com/cookieconsent
$default = [
  'display' => [
    'revokeBtn' => '<div class="cc-revoke"></div>',
  	'type' => 'opt-in',
  	'position' => 'bottom-left',
  	'theme' => 'classic',
  	'palette' => [
  		'popup' => [
  			'background' => '#000',
  			'text' => '#fff'
  		],
  		'button' => [
  			'background' => '#fd0',
  			'text' => '#000'
  		]
  	],
  	'content' => [
  		'message' => 'This website uses cookies to ensure you get the best experience on our website',
  		'link' => 'Cookie notice',
  		'allow' => 'Allow cookies',
  		'deny' => 'Decline',
  		'href' => 'https://2gdpr.com/cookies'
  	]
  ],

  'tracking' => [
    'google' => false,
    'facbook' => false
  ]
];

$default = array_merge($default, $cookie?? []);
?>
<!-- dependencies -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.1/cookieconsent.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.1/cookieconsent.min.js" data-cfasync="false"></script>

<!-- consent -->
<script>
window.addEventListener('load', function()
{
  window.cookieconsent.initialise({
    revokeBtn: '<?= Hash::get($default, 'display.revokeBtn') ?>',
    type: '<?= Hash::get($default, 'display.type') ?>',
    position: '<?= Hash::get($default, 'display.position') ?>',
    theme: '<?= Hash::get($default, 'display.theme') ?>',
    palette: {
      popup: {
        background: '<?= Hash::get($default, 'display.palette.popup.background') ?>',
        text: '<?= Hash::get($default, 'display.palette.popup.text') ?>'
      },
      button: {
        background: '<?= Hash::get($default, 'display.palette.button.background') ?>',
        text: '<?= Hash::get($default, 'display.palette.button.text') ?>'
      }
    },
    content: {
      message: '<?= Hash::get($default, 'display.content.message') ?>',
      link: '<?= Hash::get($default, 'display.content.link') ?>',
      allow: '<?= Hash::get($default, 'display.content.allow') ?>',
      deny: '<?= Hash::get($default, 'display.content.deny') ?>',
      href: '<?= Hash::get($default, 'display.content.href') ?>'
    },
    onInitialise: function(status) {
      if(status == cookieconsent.status.allow) myScripts();
    },
    onStatusChange: function(status) {
      if (this.hasConsented()) myScripts();
    }
  })

  window.getCookie = function(cname)
  {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

  window.getCsrfToken = function()
  {
    return window.getCookie('csrfToken')
  }
});

function loadScript(src)
{
  let script = document.createElement('script');
  script.src = src;
  script.async = true;
  script.defer = true;
  document.body.append(script);
}

function myScripts()
{

  // Paste here your scripts that use cookies requiring consent. See examples below

  <?php if(Hash::get($default,'tracking.google')): ?>
  // Google
  loadScript('https://www.googletagmanager.com/gtag/js?id=<?= Hash::get($default,'tracking.google') ?>');
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= Hash::get($default,'tracking.google') ?>');
  <?php endif; ?>

  <?php if(Hash::get($default,'tracking.facebook')): ?>
  // FB
  !function(f,b,e,v,n,t,s)
  {
    if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)
  }
  (window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?= Hash::get($default,'tracking.facebook') ?>');
  fbq('track', 'PageView');
  <?php endif; ?>
}
</script>
