import { ListBasics } from '@/components/training/guides/shared/list-basics';
import {
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Admin → Sent Mails (`admin.sent-mails.index`).
 * Source: documentation/admin.md §6, ARCHITECTURE.md §9.4.
 */
export default function SentMailsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Every mail the application has handed to the mail server, newest first, with a copy of what it said. Use it to answer “did they get the mail?” — and to read exactly what was sent."
                    bn="অ্যাপ্লিকেশন মেইল সার্ভারের হাতে যত ইমেইল দিয়েছে তার সবগুলো, নতুনটা আগে, সাথে প্রতিটিতে কী লেখা ছিল তার একটি কপি। “তিনি কি ইমেইলটি পেয়েছেন?” — এই প্রশ্নের উত্তর খুঁজতে, এবং ঠিক কী পাঠানো হয়েছিল তা পড়তে এটি ব্যবহার করুন।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.sent-mails.view</Ui> — super admin only
                            until someone grants it, because the stored copies
                            show what was mailed to every user.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.sent-mails.view</Ui> — কেউ না দেওয়া
                            পর্যন্ত কেবল সুপার অ্যাডমিনের, কারণ সংরক্ষিত কপিতে
                            প্রত্যেক ব্যবহারকারীকে কী ইমেইল করা হয়েছে তা দেখা
                            যায়।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Admin → Sent Mails. Type part of an address in the recipient filter, or pick a kind of mail."
                        bn="Admin → Sent Mails খুলুন। প্রাপকের ফিল্টারে ঠিকানার অংশ লিখুন, অথবা ইমেইলের ধরন বেছে নিন।"
                    >
                        <Shot
                            id="overview"
                            en="Each mail with when it was sent, to whom, its subject and its kind."
                            bn="প্রতিটি ইমেইল — কখন পাঠানো হয়েছে, কাকে, এর বিষয় ও ধরনসহ।"
                        />
                    </Step>
                    <Step
                        en="Press the eye on a row to read the mail as it was sent."
                        bn="ইমেইলটি যেভাবে পাঠানো হয়েছিল সেভাবে পড়তে সারির চোখ চিহ্নটি চাপুন।"
                    >
                        <Shot
                            id="mail"
                            en="A welcome mail open, its password masked."
                            bn="একটি স্বাগত ইমেইল খোলা অবস্থায়, পাসওয়ার্ড লুকানো।"
                        />
                    </Step>
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Sent"
                        en="When the mail was handed to the mail server."
                        bn="কখন ইমেইলটি মেইল সার্ভারের হাতে দেওয়া হয়েছে।"
                    />
                    <Field
                        label="Kind"
                        en="Which of the application's mails it is — UserWelcomeMail for a new account, UserPasswordResetMail for a password an administrator set, MailSettingTestMail for a test."
                        bn="অ্যাপ্লিকেশনের কোন ইমেইল এটি — নতুন অ্যাকাউন্টের জন্য UserWelcomeMail, অ্যাডমিনিস্ট্রেটরের দেওয়া পাসওয়ার্ডের জন্য UserPasswordResetMail, পরীক্ষার জন্য MailSettingTestMail।"
                    />
                </Fields>
                <ListBasics />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="A row here means the mail left the application, not that it arrived: the receiving side can still reject it or file it as spam. And under Log only nothing is delivered at all — the mail is written to a file on the server, yet it still appears here."
                    bn="এখানে একটি সারি থাকার মানে ইমেইলটি অ্যাপ্লিকেশন থেকে বেরিয়েছে, পৌঁছেছে এমন নয়: প্রাপকের দিক থেকে এখনো এটি প্রত্যাখ্যাত হতে পারে বা স্প্যামে যেতে পারে। আর Log only চালু থাকলে কিছুই পৌঁছায় না — ইমেইলটি সার্ভারের একটি ফাইলে লেখা হয়, তবুও এখানে দেখা যায়।"
                />
                <Callout
                    tone="info"
                    en="A mail that failed before reaching the mail server is not listed. If someone says no mail came and there is no row, ask your administrator to check the server."
                    bn="মেইল সার্ভারে পৌঁছানোর আগেই ব্যর্থ হওয়া ইমেইল এখানে থাকে না। কেউ ইমেইল পাননি বললে আর কোনো সারি না থাকলে, সার্ভারটি দেখার জন্য আপনার অ্যাডমিনিস্ট্রেটরকে বলুন।"
                />
                <Callout
                    tone="info"
                    en="Passwords inside a stored copy are masked. To give someone their password again, set a new one on the Users screen."
                    bn="সংরক্ষিত কপির ভেতরের পাসওয়ার্ড লুকানো থাকে। কাউকে আবার পাসওয়ার্ড দিতে Users স্ক্রিনে নতুন একটি দিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.application-settings"
                    en="The mail server these mails go out through."
                    bn="যে মেইল সার্ভার দিয়ে এই ইমেইলগুলো যায়।"
                />
                <RelatedGuide
                    to="admin.users"
                    en="Where the welcome and password mails start."
                    bn="স্বাগত ও পাসওয়ার্ডের ইমেইলগুলো যেখান থেকে শুরু হয়।"
                />
            </GuideSection>
        </Guide>
    );
}
