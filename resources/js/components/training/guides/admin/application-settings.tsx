import {
    Callout,
    EnumOptions,
    Field,
    Fields,
    Guide,
    GuideSection,
    Message,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    SubHeading,
    Ui,
} from '@/components/training/vocabulary';
import { MAIL_ENCRYPTION_LABELS, MAIL_TRANSPORT_LABELS } from '@/lib/enums';

/**
 * Admin → Application Settings (`admin.application-settings.edit`) — the
 * Mail Server, Quick Login and Branding tabs.
 * Source: documentation/admin.md §5, ARCHITECTURE.md §9.4, §9.9, §9.5.
 */
export default function ApplicationSettingsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Setup for the whole application, one tab per setting: how mail goes out (Mail Server), the one-click sign-in buttons used while developing (Quick Login), and the logo and browser-tab icon (Branding)."
                    bn="পুরো অ্যাপ্লিকেশনের সেটআপ, প্রতিটি সেটিংয়ের জন্য একটি ট্যাব: ইমেইল কীভাবে যায় (Mail Server), ডেভেলপমেন্টের সময় ব্যবহৃত এক-ক্লিকে সাইন-ইনের বোতাম (Quick Login), আর লোগো ও ব্রাউজার ট্যাবের আইকন (Branding)।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            Each tab has a pair:{' '}
                            <Ui>admin.mail-settings.view</Ui> /{' '}
                            <Ui>admin.mail-settings.update</Ui> and{' '}
                            <Ui>admin.quick-login.view</Ui> /{' '}
                            <Ui>admin.quick-login.update</Ui> — super admin only
                            until someone grants them — and{' '}
                            <Ui>admin.branding.view</Ui> /{' '}
                            <Ui>admin.branding.update</Ui>, which the admin role
                            holds. The page opens with any view permission; a
                            tab you cannot read is not there, and without the
                            update permission a tab is read-only.
                        </>
                    }
                    bn={
                        <>
                            প্রতিটি ট্যাবের দুটি করে পারমিশন:{' '}
                            <Ui>admin.mail-settings.view</Ui> /{' '}
                            <Ui>admin.mail-settings.update</Ui> এবং{' '}
                            <Ui>admin.quick-login.view</Ui> /{' '}
                            <Ui>admin.quick-login.update</Ui> — কেউ না দেওয়া
                            পর্যন্ত এগুলো কেবল সুপার অ্যাডমিনের — এবং{' '}
                            <Ui>admin.branding.view</Ui> /{' '}
                            <Ui>admin.branding.update</Ui>, যা অ্যাডমিন রোলের
                            আছে। যেকোনো একটি view পারমিশন থাকলেই পাতাটি খোলে; যে
                            ট্যাব পড়ার অনুমতি নেই সেটি দেখা যায় না, আর update
                            পারমিশন না থাকলে ট্যাবটি শুধু দেখা যায়, বদলানো যায়
                            না।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="For SMTP, have the mail server's address, port, encryption and account from whoever runs your mail. Ask for an address the application may send from."
                    bn="SMTP-এর জন্য, যিনি আপনার ইমেইল ব্যবস্থা চালান তাঁর কাছ থেকে মেইল সার্ভারের ঠিকানা, পোর্ট, এনক্রিপশন ও অ্যাকাউন্ট জেনে নিন। এমন একটি ঠিকানাও চেয়ে নিন যেখান থেকে অ্যাপ্লিকেশন ইমেইল পাঠাতে পারবে।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <SubHeading en="Mail Server" bn="Mail Server" />
                <Steps>
                    <Step
                        en={
                            <>
                                Open Admin → Application Settings. On the{' '}
                                <Ui>Mail Server</Ui> tab, choose{' '}
                                <Ui>Send mail by</Ui>, fill in the server, and
                                the sender address and name every mail will
                                carry.
                            </>
                        }
                        bn={
                            <>
                                Admin → Application Settings খুলুন।{' '}
                                <Ui>Mail Server</Ui> ট্যাবে{' '}
                                <Ui>Send mail by</Ui> বেছে নিন, সার্ভারের তথ্য
                                দিন, আর প্রতিটি ইমেইলে যে প্রেরকের ঠিকানা ও নাম
                                থাকবে তা লিখুন।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="The Mail Server tab with an invented SMTP server filled in."
                            bn="Mail Server ট্যাব — একটি কাল্পনিক SMTP সার্ভারের তথ্য পূরণ করা অবস্থায়।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>Save</Ui>. The next mail goes out with
                                the new settings.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Save</Ui> চাপুন। পরের ইমেইলটিই নতুন সেটিংস
                                দিয়ে যায়।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                Press <Ui>Send test mail</Ui>. It goes to your
                                own address straight away, so a wrong setting
                                shows at once, with the server's reason.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Send test mail</Ui> চাপুন। এটি সাথে সাথে
                                আপনার নিজের ঠিকানায় যায়, তাই কোনো সেটিং ভুল
                                হলে সার্ভারের দেওয়া কারণসহ তখনই দেখা যায়।
                            </>
                        }
                    />
                </Steps>

                <SubHeading en="Quick Login" bn="Quick Login" />
                <Steps>
                    <Step
                        en={
                            <>
                                On the <Ui>Quick Login</Ui> tab, switch it on or
                                off and press <Ui>Save</Ui>. The table shows
                                which buttons the login page will offer.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Quick Login</Ui> ট্যাবে এটি চালু বা বন্ধ করে{' '}
                                <Ui>Save</Ui> চাপুন। টেবিলে দেখা যায় লগইন
                                পাতায় কোন কোন বোতাম থাকবে।
                            </>
                        }
                    >
                        <Shot
                            id="quick-login"
                            en="The Quick Login tab: the switch, its warning, and the accounts that get a button."
                            bn="Quick Login ট্যাব: সুইচ, এর সতর্কবার্তা, আর যে অ্যাকাউন্টগুলোর বোতাম থাকবে।"
                        />
                    </Step>
                </Steps>

                <SubHeading en="Branding" bn="Branding" />
                <Steps>
                    <Step
                        en={
                            <>
                                On the <Ui>Branding</Ui> tab, choose an image on
                                the <Ui>Logo</Ui> or <Ui>Favicon</Ui> card and
                                press <Ui>Upload</Ui> (<Ui>Replace</Ui> once one
                                is set). The sidebar shows a new logo at once;
                                the browser tab shows a new icon on the next
                                page load.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Branding</Ui> ট্যাবে <Ui>Logo</Ui> বা{' '}
                                <Ui>Favicon</Ui> কার্ডে একটি ছবি বেছে{' '}
                                <Ui>Upload</Ui> চাপুন (একটি থাকলে{' '}
                                <Ui>Replace</Ui>)। নতুন লোগো সাইডবারে সাথে সাথে
                                দেখা যায়; নতুন আইকন ব্রাউজার ট্যাবে দেখা যায়
                                পরের বার পাতা লোড হলে।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                To go back to the default, press{' '}
                                <Ui>Remove logo</Ui> or <Ui>Remove favicon</Ui>{' '}
                                and confirm. The image file is deleted.
                            </>
                        }
                        bn={
                            <>
                                আগের ডিফল্টে ফিরতে <Ui>Remove logo</Ui> বা{' '}
                                <Ui>Remove favicon</Ui> চেপে নিশ্চিত করুন। ছবির
                                ফাইলটি মুছে ফেলা হয়।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Send mail by"
                        en="Whether mail really goes out."
                        bn="ইমেইল সত্যিই পাঠানো হবে কিনা।"
                    >
                        <EnumOptions
                            labels={MAIL_TRANSPORT_LABELS}
                            explain={{
                                smtp: {
                                    en: 'Mail is delivered through the server below.',
                                    bn: 'নিচের সার্ভারের মাধ্যমে ইমেইল পৌঁছে দেওয়া হয়।',
                                },
                                log: {
                                    en: 'Nothing is delivered: each mail is written to a file on the server instead. For testing. The server fields are kept as they were for when you switch back.',
                                    bn: 'কিছুই পৌঁছায় না: প্রতিটি ইমেইল বরং সার্ভারের একটি ফাইলে লেখা হয়। পরীক্ষার জন্য। আবার SMTP-তে ফিরলে যাতে পাওয়া যায়, সেজন্য সার্ভারের ঘরগুলো আগের মতোই রাখা থাকে।',
                                },
                            }}
                        />
                    </Field>
                    <Field
                        label="Encryption"
                        en="How the connection to the server is protected. Your mail provider says which."
                        bn="সার্ভারের সাথে সংযোগ কীভাবে সুরক্ষিত হবে। কোনটি লাগবে তা আপনার মেইল প্রোভাইডার বলে দেবেন।"
                    >
                        <EnumOptions
                            labels={MAIL_ENCRYPTION_LABELS}
                            explain={{
                                ssl: {
                                    en: 'Encrypted from the first byte. Usually with port 465.',
                                    bn: 'প্রথম থেকেই এনক্রিপ্টেড। সাধারণত ৪৬৫ পোর্টের সাথে।',
                                },
                                starttls: {
                                    en: 'Starts plain, then switches to encryption. Usually with port 587.',
                                    bn: 'সাধারণভাবে শুরু হয়, তারপর এনক্রিপশনে চলে যায়। সাধারণত ৫৮৭ পোর্টের সাথে।',
                                },
                                none: {
                                    en: 'Not encrypted. Only for a server inside your own network.',
                                    bn: 'এনক্রিপ্টেড নয়। কেবল আপনার নিজের নেটওয়ার্কের ভেতরের সার্ভারের জন্য।',
                                },
                            }}
                        />
                    </Field>
                    <Field
                        label="Password"
                        en="Stored encrypted and never shown again. Leave it blank to keep the saved one; type a new one to replace it."
                        bn="এনক্রিপ্ট করে রাখা হয়, আর কখনো আবার দেখানো হয় না। সংরক্ষিত পাসওয়ার্ডটি রাখতে চাইলে ঘরটি ফাঁকা রাখুন; বদলাতে নতুনটি লিখুন।"
                    />
                    <Field
                        label="Sender address"
                        en="The From of every mail. Many servers refuse an address they do not own."
                        bn="প্রতিটি ইমেইলের From। অনেক সার্ভার নিজেদের নয় এমন ঠিকানা থেকে ইমেইল পাঠাতে দেয় না।"
                    />
                    <Field
                        label="Quick login is on"
                        en="One-click sign-in buttons on the login page for the demo accounts. They appear only on a development or test copy of the application — never on the live one, whatever the switch says. An account that is missing or inactive gets no button."
                        bn="লগইন পাতায় ডেমো অ্যাকাউন্টগুলোর জন্য এক-ক্লিকে সাইন-ইনের বোতাম। এগুলো কেবল অ্যাপ্লিকেশনের ডেভেলপমেন্ট বা টেস্ট কপিতে দেখা যায় — লাইভ কপিতে কখনো নয়, সুইচ যা-ই বলুক। যে অ্যাকাউন্ট নেই বা নিষ্ক্রিয়, তার কোনো বোতাম থাকে না।"
                    />
                    <Field
                        label="Logo"
                        en="Shown in the sidebar beside the application name and large on the sign-in screens. PNG, JPG or WebP, up to 1 MB. One image serves every theme, light and dark, so a transparent background works best."
                        bn="সাইডবারে অ্যাপ্লিকেশনের নামের পাশে, আর সাইন-ইন পাতায় বড় করে দেখানো হয়। PNG, JPG বা WebP, ১ MB পর্যন্ত। একটি ছবিই সব থিমে — হালকা ও গাঢ় — ব্যবহার হয়, তাই স্বচ্ছ পটভূমির ছবি সবচেয়ে ভালো দেখায়।"
                    />
                    <Field
                        label="Favicon"
                        en="The small icon in the browser tab. A square PNG, or an ICO, up to 256 KB. Without one, the tab uses the logo; without either, the default icon."
                        bn="ব্রাউজার ট্যাবের ছোট আইকন। বর্গাকার PNG, অথবা ICO, ২৫৬ KB পর্যন্ত। না থাকলে ট্যাবে লোগো দেখায়; দুটোর কোনোটিই না থাকলে ডিফল্ট আইকন।"
                    />
                </Fields>
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="Mail settings saved. The next mail goes out with them."
                    en="Saved. Send a test mail to be sure they work."
                    bn="সেভ হয়েছে। সেটিংস ঠিকমতো কাজ করছে কিনা নিশ্চিত হতে একটি টেস্ট মেইল পাঠান।"
                />
                <Message
                    text="Test mail sent to rahim@example.com."
                    en="The server accepted it. Check that inbox — and its spam folder."
                    bn="সার্ভার এটি গ্রহণ করেছে। সেই ইনবক্সটি — আর এর স্প্যাম ফোল্ডারও — দেখুন।"
                />
                <Message
                    text="Test mail written to the log (Log only). Nobody received it."
                    en="Send mail by is Log only, so the test was written to a file, not sent."
                    bn="Send mail by-এ Log only বেছে নেওয়া আছে, তাই টেস্ট মেইলটি পাঠানো হয়নি, একটি ফাইলে লেখা হয়েছে।"
                />
                <Message
                    text="The test mail failed: …"
                    en="The server refused, followed by its own reason — usually a wrong host, port, encryption or password."
                    bn="সার্ভার প্রত্যাখ্যান করেছে, পরে এর নিজের দেওয়া কারণ — সাধারণত ভুল হোস্ট, পোর্ট, এনক্রিপশন বা পাসওয়ার্ড।"
                />
                <Message
                    text="The saved password cannot be read"
                    en="The password was saved on another copy of the application, usually after the database was copied from another machine. Type the password again and save; until then, mail fails."
                    bn="পাসওয়ার্ডটি অ্যাপ্লিকেশনের অন্য একটি কপিতে সেভ করা হয়েছিল — সাধারণত ডেটাবেস অন্য মেশিন থেকে কপি করার পর এমন হয়। পাসওয়ার্ডটি আবার লিখে সেভ করুন; ততক্ষণ ইমেইল যাবে না।"
                />
                <Message
                    text="Quick login switched on. The buttons appear on the login page in local and testing only."
                    en="Saved. On the live application the buttons still never show."
                    bn="সেভ হয়েছে। লাইভ অ্যাপ্লিকেশনে বোতামগুলো তবুও কখনো দেখা যায় না।"
                />
                <Message
                    text="Branding saved. The browser tab icon updates on the next page load."
                    en="The new image is stored. Reload any open tab to see its new icon."
                    bn="নতুন ছবিটি সংরক্ষিত হয়েছে। খোলা কোনো ট্যাবে নতুন আইকন দেখতে পাতাটি আবার লোড করুন।"
                />
                <Message
                    text="The favicon must be square."
                    en="A PNG favicon must be as wide as it is tall. Crop it, or upload an ICO."
                    bn="PNG ফেভিকনের প্রস্থ ও উচ্চতা সমান হতে হবে। ছবিটি কেটে বর্গাকার করুন, অথবা একটি ICO আপলোড করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Saving is accepted even when the settings are wrong — nothing tests them on save. Every mail after it fails, including new users' welcome mails. Always send a test mail after changing anything here."
                    bn="সেটিংস ভুল হলেও সেভ হয়ে যায় — সেভ করার সময় কিছুই যাচাই করা হয় না। এরপরের প্রতিটি ইমেইল ব্যর্থ হয়, নতুন ব্যবহারকারীদের স্বাগত ইমেইলসহ। এখানে কিছু বদলালেই সবসময় একটি টেস্ট মেইল পাঠান।"
                />
                <Callout
                    tone="warn"
                    en="Send test mail uses the saved settings, not what is typed in the form. Save first."
                    bn="Send test mail সংরক্ষিত সেটিংস ব্যবহার করে, ফর্মে যা লেখা আছে তা নয়। আগে সেভ করুন।"
                />
                <Callout
                    tone="warn"
                    en="While quick login is on, anyone who can open the login page can sign in as the demo accounts — a super admin among them — with one click. Switch it off when you are done."
                    bn="কুইক লগইন চালু থাকলে লগইন পাতা খুলতে পারেন এমন যে কেউ এক ক্লিকে ডেমো অ্যাকাউন্টগুলো দিয়ে সাইন ইন করতে পারেন — যার মধ্যে একজন সুপার অ্যাডমিনও আছেন। কাজ শেষ হলে এটি বন্ধ করুন।"
                />
                <Callout
                    tone="warn"
                    en="SVG images are refused — an SVG can carry code. Export the logo as a PNG with a transparent background instead."
                    bn="SVG ছবি গ্রহণ করা হয় না — একটি SVG-এর ভেতরে কোড থাকতে পারে। এর বদলে লোগোটি স্বচ্ছ পটভূমির PNG হিসেবে এক্সপোর্ট করুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.sent-mails"
                    en="Every mail that went out with these settings."
                    bn="এই সেটিংস দিয়ে যত ইমেইল গেছে তার সব।"
                />
            </GuideSection>
        </Guide>
    );
}
