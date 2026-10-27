import { ListBasics } from '@/components/training/guides/shared/list-basics';
import {
    Callout,
    EnumOptions,
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
import { AUDIT_EVENT_LABELS } from '@/lib/enums';

/**
 * Admin → Audit Log (`admin.audit-logs.index`).
 * Source: documentation/admin.md §4.
 */
export default function AuditLogsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Every recorded change in the application: when, who made it, which record, what happened and which fields moved — plus every sign-in, sign-out and failed sign-in. Records are written automatically and cannot be edited or removed."
                    bn="অ্যাপ্লিকেশনের প্রতিটি রেকর্ড করা পরিবর্তন: কখন, কে করেছেন, কোন রেকর্ডে, কী ঘটেছে এবং কোন ফিল্ড বদলেছে — সাথে প্রতিটি সাইন-ইন, সাইন-আউট ও ব্যর্থ সাইন-ইন। রেকর্ডগুলো নিজে থেকেই লেখা হয় এবং সম্পাদনা বা মোছা যায় না।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.audit-logs.view</Ui> — super admin only
                            until someone grants it, because the trail shows
                            every recorded value in the application.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.audit-logs.view</Ui> — কেউ না দেওয়া
                            পর্যন্ত কেবল সুপার অ্যাডমিনের, কারণ ট্রেইলে
                            অ্যাপ্লিকেশনের প্রতিটি রেকর্ড করা মান দেখা যায়।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Admin → Audit Log. The newest change is at the top. Narrow it by who, what kind of record, which event or which IP address."
                        bn="Admin → Audit Log খুলুন। সবচেয়ে নতুন পরিবর্তন সবার ওপরে। কে, কোন ধরনের রেকর্ড, কোন ঘটনা বা কোন IP ঠিকানা — এসব দিয়ে তালিকা ছোট করুন।"
                    >
                        <Shot
                            id="overview"
                            en="One row per change: when, who, which record, the event, the fields that moved and the IP address."
                            bn="প্রতিটি পরিবর্তনের জন্য একটি সারি: কখন, কে, কোন রেকর্ড, ঘটনা, যে ফিল্ডগুলো বদলেছে এবং IP ঠিকানা।"
                        />
                    </Step>
                    <Step
                        en="Press the eye on a row to see that change field by field — before and after — with the address, IP and browser it came from."
                        bn="কোনো সারির চোখ চিহ্নটি চাপলে সেই পরিবর্তনটি ফিল্ড ধরে ধরে দেখা যায় — আগে ও পরে — সাথে কোন ঠিকানা, IP ও ব্রাউজার থেকে এসেছে।"
                    >
                        <Shot
                            id="detail"
                            en="One change open: Field, Before and After, with the URL, IP address and browser."
                            bn="একটি পরিবর্তন খোলা অবস্থায়: Field, Before ও After, সাথে URL, IP ঠিকানা ও ব্রাউজার।"
                        />
                    </Step>
                    <Step
                        en="Press the clock to see the whole history of that record, newest first — every change anyone ever made to it."
                        bn="ঘড়ি চিহ্নটি চাপলে সেই রেকর্ডের পুরো ইতিহাস দেখা যায়, নতুনটা আগে — যে কেউ যত পরিবর্তন করেছেন তার সব।"
                    >
                        <Shot
                            id="history"
                            en="A user's full history, one block per change."
                            bn="একজন ব্যবহারকারীর পুরো ইতিহাস, প্রতিটি পরিবর্তনের জন্য একটি করে অংশ।"
                        />
                    </Step>
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Who"
                        en="Who made the change, by name as it was at the time — a user deleted since still shows. System means no one was signed in, such as a failed sign-in."
                        bn="কে পরিবর্তনটি করেছেন, তখন তাঁর যে নাম ছিল সেই নামে — পরে মুছে ফেলা ব্যবহারকারীর নামও দেখা যায়। System মানে তখন কেউ সাইন ইন করা ছিলেন না, যেমন ব্যর্থ সাইন-ইনের বেলায়।"
                    />
                    <Field
                        label="Record"
                        en="The kind of record and its number — a user, a role, a permission, the mail server or the quick login setting."
                        bn="রেকর্ডের ধরন ও এর নম্বর — একজন ব্যবহারকারী, একটি রোল, একটি পারমিশন, মেইল সার্ভার বা কুইক লগইন সেটিং।"
                    />
                    <Field label="Event" en="What happened." bn="কী ঘটেছে।">
                        <EnumOptions
                            labels={AUDIT_EVENT_LABELS}
                            explain={{
                                created: {
                                    en: 'The record was added.',
                                    bn: 'রেকর্ডটি যোগ করা হয়েছে।',
                                },
                                updated: {
                                    en: 'Fields were changed; the row lists which.',
                                    bn: 'ফিল্ড বদলানো হয়েছে; সারিতে লেখা থাকে কোনগুলো।',
                                },
                                deleted: {
                                    en: 'Moved to Deleted. It can still be restored.',
                                    bn: 'Deleted-এ সরানো হয়েছে। এখনো ফিরিয়ে আনা যায়।',
                                },
                                restored: {
                                    en: 'Brought back from Deleted.',
                                    bn: 'Deleted থেকে ফিরিয়ে আনা হয়েছে।',
                                },
                                'roles-changed': {
                                    en: "A user's roles were changed, with the roles before and after.",
                                    bn: 'একজন ব্যবহারকারীর রোল বদলানো হয়েছে — আগের ও পরের রোলসহ।',
                                },
                                'permissions-changed': {
                                    en: "A role's permissions were changed, with the permissions before and after.",
                                    bn: 'একটি রোলের পারমিশন বদলানো হয়েছে — আগের ও পরের পারমিশনসহ।',
                                },
                                'password-reset': {
                                    en: "An administrator set someone's password. The password itself is never recorded.",
                                    bn: 'একজন অ্যাডমিনিস্ট্রেটর কারো পাসওয়ার্ড দিয়েছেন। পাসওয়ার্ডটি নিজে কখনো রেকর্ড করা হয় না।',
                                },
                                'force-deleted': {
                                    en: 'Removed for good. The row keeps who the record was.',
                                    bn: 'চিরতরে মুছে ফেলা হয়েছে। সারিতে রেকর্ডটি কী ছিল তা থেকে যায়।',
                                },
                                'user-welcome-mailed': {
                                    en: 'A new user was mailed their sign-in details, and to which address.',
                                    bn: 'একজন নতুন ব্যবহারকারীকে তাঁর সাইন-ইনের তথ্য ইমেইল করা হয়েছে, এবং কোন ঠিকানায়।',
                                },
                                'user-password-reset-mailed': {
                                    en: 'A user was mailed a password an administrator set.',
                                    bn: 'অ্যাডমিনিস্ট্রেটরের দেওয়া পাসওয়ার্ড একজন ব্যবহারকারীকে ইমেইল করা হয়েছে।',
                                },
                                'mail-password-changed': {
                                    en: "The mail server's password was changed. The password itself is never recorded.",
                                    bn: 'মেইল সার্ভারের পাসওয়ার্ড বদলানো হয়েছে। পাসওয়ার্ডটি নিজে কখনো রেকর্ড করা হয় না।',
                                },
                                'mail-test-sent': {
                                    en: 'Someone sent a test mail from Application Settings.',
                                    bn: 'কেউ Application Settings থেকে একটি টেস্ট মেইল পাঠিয়েছেন।',
                                },
                                'logged-in': {
                                    en: 'Someone signed in.',
                                    bn: 'কেউ সাইন ইন করেছেন।',
                                },
                                'logged-out': {
                                    en: 'Someone signed out.',
                                    bn: 'কেউ সাইন আউট করেছেন।',
                                },
                                'login-failed': {
                                    en: 'A sign-in was refused. The row keeps the username or email typed, never the password.',
                                    bn: 'একটি সাইন-ইন প্রত্যাখ্যাত হয়েছে। সারিতে টাইপ করা ইউজারনেম বা ইমেইল থাকে, পাসওয়ার্ড কখনো নয়।',
                                },
                            }}
                        />
                    </Field>
                    <Field
                        label="Changed"
                        en="The fields that moved. Open the row for their old and new values; a user mentioned inside a value is shown by name."
                        bn="যে ফিল্ডগুলো বদলেছে। এদের আগের ও নতুন মান দেখতে সারিটি খুলুন; কোনো মানের ভেতরে উল্লেখ করা ব্যবহারকারীকে তাঁর নামে দেখানো হয়।"
                    />
                </Fields>
                <ListBasics />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="info"
                    en="Changes made from the server's console — outside the screens — are not recorded. If a change has no row here, it was not made through the application."
                    bn="সার্ভারের কনসোল থেকে — স্ক্রিনের বাইরে — করা পরিবর্তন রেকর্ড হয় না। কোনো পরিবর্তনের সারি এখানে না থাকলে, সেটি অ্যাপ্লিকেশনের মাধ্যমে করা হয়নি।"
                />
                <Callout
                    tone="info"
                    en="Passwords — a user's or the mail server's — are never written to the trail. A password change shows as its own event, with no values."
                    bn="পাসওয়ার্ড — ব্যবহারকারীর হোক বা মেইল সার্ভারের — কখনো ট্রেইলে লেখা হয় না। পাসওয়ার্ড বদলানো একটি আলাদা ঘটনা হিসেবে দেখায়, কোনো মান ছাড়া।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.users"
                    en="The accounts whose changes appear here most often."
                    bn="যে অ্যাকাউন্টগুলোর পরিবর্তন এখানে সবচেয়ে বেশি দেখা যায়।"
                />
                <RelatedGuide
                    to="admin.sent-mails"
                    en="The mails themselves, not just the record that they went out."
                    bn="ইমেইলগুলো নিজেই — শুধু পাঠানো হয়েছে এই রেকর্ড নয়।"
                />
            </GuideSection>
        </Guide>
    );
}
