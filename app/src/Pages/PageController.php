<?php


    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\CMS\Search\SearchForm;
    use SilverStripe\View\Requirements;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\TextField;
    use SilverStripe\Forms\EmailField;
    use SilverStripe\Forms\TextareaField;
    use SilverStripe\Forms\HiddenField;
    use SilverStripe\Forms\Form;
    use SilverStripe\Forms\FormAction;
    use SilverStripe\Forms\Validation\RequiredFieldsValidator;
    use SilverStripe\ORM\Search\FulltextSearchable;
    use SilverStripe\Core\Validation\ValidationResult;
    use SilverStripe\Control\Email\Email;
    use SilverStripe\Control\Director;
    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\Model\List\ArrayList;
    use App\Models\FormSubmission;
    use SilverStripe\ORM\FieldType\DBDatetime;
    use Antlion\StoreLocation\Pages\StoreLocationPage;

    class PageController extends ContentController
    {

        private static $allowed_actions = [
            'ContactForm',
            'doContactForm',
            'SearchForm',
        ];

        /**
         * Overrides ContentControllerSearchExtension::SearchForm() (added by
         * FulltextSearchable::enable(), see app/_config.php) purely to style
         * the field/button with daisyUI join classes -- markup for the
         * wrapper itself lives in Includes/Nav/SearchForm.ss.
         */
        public function SearchForm()
        {
            $searchText = '';
            if ($this->getRequest() && $this->getRequest()->getVar('Search')) {
                $searchText = $this->getRequest()->getVar('Search');
            }

            $fields = FieldList::create(
                TextField::create('Search', false, $searchText)
                    ->setAttribute('type', 'search')
                    ->setAttribute('placeholder', 'Search...')
                    ->setAttribute('aria-label', 'Search')
                    ->addExtraClass('join-item')
            );

            $actions = FieldList::create(
                FormAction::create('results', 'Search')
                    ->setAttribute('type', 'submit')
                    ->addExtraClass('btn btn-primary join-item')
            );

            $form = SearchForm::create($this, 'SearchForm', $fields, $actions);
            $form->classesToSearch(FulltextSearchable::get_searchable_classes());

            return $form;
        }

        protected function init()
        {
            parent::init();
            Requirements::set_force_js_to_bottom(true);
            Requirements::block('silverstripe/userforms:client/dist/js/jquery.min.js');
            Requirements::block('innoweb/silverstripe-cookie-consent:client/dist/css/cookieconsent.css');
            // CSS/JS come from the theme's templates (silverwind: dist/ via themedCSS/themedJavascript)

        }

        public function ContactForm(): Form
        {
            $fields = FieldList::create(
                TextField::create('Name', 'Full Name*')
                    ->setAttribute('autocomplete', 'name'),
                EmailField::create('Email', 'Email*')
                    ->setAttribute('autocomplete', 'email'),
                TextField::create('Phone', 'Phone (optional)')
                    ->setAttribute('autocomplete', 'tel'),
                TextareaField::create('Message', 'Question or Comment*')
                    ->setRows(6),

                // Context + CRM helpers
                HiddenField::create('PageURL', '', $this->AbsoluteLink()),
                HiddenField::create('CRMSource', '', 'page-contact'),
                HiddenField::create('PageID', '', (string)$this->ID),
                HiddenField::create('PageTitle', '', $this->Title)
            );

            $actions = FieldList::create(
                FormAction::create('doContactForm', 'Send message')
                    ->addExtraClass('button primary')
            );

            $required = RequiredFieldsValidator::create(['Name', 'Email', 'Message']);
            $form     = Form::create($this, 'ContactForm', $fields, $actions, $required);

            if (method_exists($form, 'enableSpamProtection')) {
                $form->enableSpamProtection();
            }

            return $form;
        }

        public function doContactForm(array $data, Form $form)
        {
            // Basic validation guard (RequiredFields also runs, but this keeps it consistent)
            if (empty($data['Name']) || empty($data['Email']) || empty($data['Message'])) {
                $form->sessionMessage('Please complete the required fields.', ValidationResult::TYPE_ERROR);
                return $this->redirectBack();
            }

            // Work out who to send to
            $site = SiteConfig::current_site_config();

            // Prefer a MailTo field on the Page (if you add one later), then SiteConfig SupportEmail / Email
            $pageMailTo = $this->data()->MailTo ?? null; // safe even if field doesn't exist
            $to = $pageMailTo
                ?: ($site->SupportEmail ?? null)
                ?: ($site->Email ?? null)
                ?: 'webmaster@localhost';

            $sentTo = is_array($to) ? implode(', ', $to) : (string)$to;

            $fromHost = (string) parse_url(Director::absoluteBaseURL(), PHP_URL_HOST);
            $from     = 'noreply@' . $fromHost;
            $replyTo  = trim($data['Email'] ?? '');

            $pageTitle = $this->Title;
            // Always derive the URL server-side: the PageURL hidden field is
            // user-controlled and must not be trusted, stored or emailed.
            $pageURL   = $this->AbsoluteLink();

            $subject = sprintf('Website enquiry – %s', $pageTitle);

            // Template data for emails / submissions
            $templateData = [
                'Name'        => $data['Name'] ?? '',
                'Email'       => $data['Email'] ?? '',
                'Phone'       => $data['Phone'] ?? '',
                'Message'     => $data['Message'] ?? '',
                'PageTitle'   => $pageTitle,
                'PageUrl'     => $pageURL,
                'SubmittedAt' => DBDatetime::now()->Nice(),
            ];

            // Log to FormSubmission
            $submission = FormSubmission::create([
                'FormName'       => 'Generic contact',
                'FormAction'     => 'ContactForm',
                'PageID'         => $this->ID,
                'Context'        => $pageTitle,
                'ContextLink'    => $pageURL,
                'SubmitterName'  => $templateData['Name'],
                'SubmitterEmail' => $templateData['Email'],
                'SubmitterPhone' => $templateData['Phone'],
                'Message'        => $templateData['Message'],
                'SentTo'         => $sentTo,
                'RawData'        => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);
            $submission->write();

            // Basic HTML email (you can swap to a template later). Every value
            // is escaped: submitted fields must never be interpreted as HTML.
            $e = array_map(
                fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $templateData
            );

            $body = <<<HTML
                <p><strong>Website enquiry</strong></p>
                <p><strong>Page:</strong> {$e['PageTitle']}<br>
                <strong>Link:</strong> {$e['PageUrl']}</p>

                <p><strong>Name:</strong> {$e['Name']}<br>
                <strong>Email:</strong> {$e['Email']}<br>
                <strong>Phone:</strong> {$e['Phone']}</p>

                <p><strong>Message:</strong><br>
                <pre style="white-space:pre-wrap; font-family:inherit">{$e['Message']}</pre></p>
            HTML;

            $email = Email::create()
                ->setTo($to)
                ->setFrom($from)
                ->setSubject($subject)
                ->setBody($body);

            if ($replyTo) {
                $email->setReplyTo($replyTo);
            }

            try {
                $email->send();
                $submission->Status = 'Emailed';
                $submission->write();

                $form->sessionMessage('Thanks! Your message has been sent.', ValidationResult::TYPE_GOOD);
            } catch (\Throwable $e) {
                $submission->Status       = 'Error';
                $submission->ErrorMessage = $e->getMessage();
                $submission->write();

                $form->sessionMessage('Sorry, something went wrong sending your message.', ValidationResult::TYPE_ERROR);
            }

            return $this->redirectBack();
        }

         public function getLocations()
            {
                // antlion/store-location is optional; without it there are no locations
                if (!class_exists(StoreLocationPage::class)) {
                    return ArrayList::create();
                }

                return StoreLocationPage::get()->sort(
                    'Title ASC'
                );
            }
    }