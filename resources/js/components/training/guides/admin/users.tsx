import { ListBasics } from '@/components/training/guides/shared/list-basics';
import {
    Bullet,
    Bullets,
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
import { RECORD_STATUS_LABELS } from '@/lib/enums';

/**
 * Admin → Users (`admin.users.index`).
 * Source: documentation/admin.md §2.
 */
export default function UsersGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Everyone who can sign in: their account details, their roles and their password. Accounts are created here only — there is no way to sign up. Deleting a user moves them to the Deleted tab, where they can be restored or removed for good."
                    bn="যাঁরা সাইন ইন করতে পারেন, তাঁদের সবাই: তাঁদের অ্যাকাউন্টের তথ্য, রোল ও পাসওয়ার্ড। অ্যাকাউন্ট কেবল এখান থেকেই তৈরি হয় — নিজে নিজে সাইন আপ করার কোনো উপায় নেই। কোনো ব্যবহারকারীকে মুছলে তিনি Deleted ট্যাবে চলে যান; সেখান থেকে ফিরিয়ে আনা যায়, অথবা চিরতরে মুছে ফেলা যায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Bullets>
                    <Bullet
                        en={
                            <>
                                <Ui>admin.users.view</Ui> opens the list;{' '}
                                <Ui>admin.users.create</Ui> and{' '}
                                <Ui>admin.users.update</Ui> add and edit
                                accounts.
                            </>
                        }
                        bn={
                            <>
                                <Ui>admin.users.view</Ui> থাকলে তালিকা খোলা
                                যায়; <Ui>admin.users.create</Ui> ও{' '}
                                <Ui>admin.users.update</Ui> দিয়ে অ্যাকাউন্ট যোগ
                                ও সম্পাদনা করা যায়।
                            </>
                        }
                    />
                    <Bullet
                        en={
                            <>
                                Each other action has its own permission:{' '}
                                <Ui>admin.users.assign-roles</Ui>,{' '}
                                <Ui>admin.users.assign-companies</Ui>,{' '}
                                <Ui>admin.users.reset-password</Ui>,{' '}
                                <Ui>admin.users.delete</Ui>,{' '}
                                <Ui>admin.users.restore</Ui> and{' '}
                                <Ui>admin.users.force-delete</Ui>. A button you
                                hold no permission for is simply not there.
                            </>
                        }
                        bn={
                            <>
                                বাকি প্রতিটি কাজের আলাদা পারমিশন:{' '}
                                <Ui>admin.users.assign-roles</Ui>,{' '}
                                <Ui>admin.users.assign-companies</Ui>,{' '}
                                <Ui>admin.users.reset-password</Ui>,{' '}
                                <Ui>admin.users.delete</Ui>,{' '}
                                <Ui>admin.users.restore</Ui> ও{' '}
                                <Ui>admin.users.force-delete</Ui>। যে কাজের
                                পারমিশন আপনার নেই, তার বোতাম দেখাই যায় না।
                            </>
                        }
                    />
                </Bullets>
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                Open Admin → Users. Find someone with the column
                                filters; your own row is marked <Ui>you</Ui>.
                            </>
                        }
                        bn={
                            <>
                                Admin → Users খুলুন। কলামের ফিল্টার দিয়ে কাউকে
                                খুঁজুন; আপনার নিজের সারিতে <Ui>you</Ui> লেখা
                                থাকে।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="Each user with their username, code, phone, roles, status and last sign-in, and the row actions."
                            bn="প্রতিটি ব্যবহারকারী — ইউজারনেম, কোড, ফোন, রোল, স্ট্যাটাস ও শেষ সাইন-ইনসহ — এবং সারির কাজের বোতামগুলো।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New user</Ui>. Give their full name, a
                                username and an email — they can sign in with
                                either — and a password that meets every line of
                                the checklist under it. Username and email are
                                checked as you type: <Ui>Available</Ui> or{' '}
                                <Ui>Already taken</Ui>. Tick their roles. Leave{' '}
                                <Ui>
                                    Email this user their username and password.
                                </Ui>{' '}
                                ticked to mail them their sign-in details.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New user</Ui> চাপুন। পুরো নাম, একটি ইউজারনেম
                                ও একটি ইমেইল দিন — এ দুটির যেকোনোটি দিয়ে তিনি
                                সাইন ইন করতে পারবেন — আর এমন একটি পাসওয়ার্ড দিন
                                যা নিচের চেকলিস্টের প্রতিটি শর্ত পূরণ করে। টাইপ
                                করার সময়ই ইউজারনেম ও ইমেইল যাচাই হয়:{' '}
                                <Ui>Available</Ui> অথবা <Ui>Already taken</Ui>।
                                তাঁর রোলগুলোতে টিক দিন।{' '}
                                <Ui>
                                    Email this user their username and password.
                                </Ui>{' '}
                                -এ টিক রেখে দিলে তাঁর সাইন-ইনের তথ্য ইমেইলে
                                পাঠানো হয়।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New user: photo, name, username, email, phone, employee code, status, password, the email tick box and roles."
                            bn="New user: ছবি, নাম, ইউজারনেম, ইমেইল, ফোন, এমপ্লয়ি কোড, স্ট্যাটাস, পাসওয়ার্ড, ইমেইলের টিক বক্স ও রোল।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Finish with <Ui>Save &amp; close</Ui>, or{' '}
                                <Ui>Save &amp; add another</Ui> to clear the
                                form and enter the next person.
                            </>
                        }
                        bn={
                            <>
                                শেষে <Ui>Save &amp; close</Ui> চাপুন, অথবা পরের
                                জনকে লিখতে <Ui>Save &amp; add another</Ui> চাপুন
                                — ফর্মটি খালি হয়ে খোলা থাকে।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                To change what someone may do, press the shield
                                on their row. Tick the roles they should hold
                                and save; the user is told in their
                                notifications.
                            </>
                        }
                        bn={
                            <>
                                কেউ কী করতে পারবেন তা বদলাতে তাঁর সারির ঢাল
                                চিহ্নটি চাপুন। যে রোলগুলো তাঁর থাকা উচিত
                                সেগুলোতে টিক দিয়ে সেভ করুন; ব্যবহারকারী তাঁর
                                নোটিফিকেশনে খবরটি পান।
                            </>
                        }
                    >
                        <Shot
                            id="roles"
                            en="Roles for a user: one tick box per role."
                            bn="একজন ব্যবহারকারীর রোল: প্রতিটি রোলের জন্য একটি টিক বক্স।"
                        />
                    </Step>
                    <Step
                        en="To decide which companies someone works in, press the building on their row and tick the companies. Without one they can still sign in, but Projects, Accounting and HRM show them nothing. The super admin needs none: they can work in every active company."
                        bn="কেউ কোন কোন কোম্পানিতে কাজ করবেন তা ঠিক করতে তাঁর সারির ভবন চিহ্নটি চাপুন এবং কোম্পানিগুলোতে টিক দিন। কোনো কোম্পানি না থাকলেও তিনি সাইন ইন করতে পারবেন, কিন্তু Projects, Accounting ও HRM-এ কিছুই দেখবেন না। super admin-এর কোনোটি লাগে না: তিনি সব সক্রিয় কোম্পানিতে কাজ করতে পারেন।"
                    >
                        <Shot
                            id="companies"
                            en="Companies for a user: one tick box per company, inactive ones marked."
                            bn="একজন ব্যবহারকারীর কোম্পানি: প্রতিটি কোম্পানির জন্য একটি টিক বক্স, নিষ্ক্রিয়গুলো চিহ্নিত।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                When someone has forgotten their password, press
                                the key on their row and set a new one. With the
                                email box ticked they are mailed it; untick it
                                to hand it over yourself.
                            </>
                        }
                        bn={
                            <>
                                কেউ পাসওয়ার্ড ভুলে গেলে তাঁর সারির চাবি চিহ্নটি
                                চেপে নতুন একটি দিন। ইমেইলের বক্সে টিক থাকলে
                                তাঁকে সেটি ইমেইলে পাঠানো হয়; নিজে জানাতে চাইলে
                                টিক তুলে দিন।
                            </>
                        }
                    >
                        <Shot
                            id="password"
                            en="Set a password: the new password with its checklist, the confirmation and the email tick box."
                            bn="Set a password: চেকলিস্টসহ নতুন পাসওয়ার্ড, নিশ্চিতকরণ ও ইমেইলের টিক বক্স।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                When someone leaves, edit them and set{' '}
                                <Ui>Account status</Ui> to <Ui>Inactive</Ui>, or
                                delete them with the red bin. A deleted user
                                waits on the <Ui>Deleted</Ui> tab: the arrow
                                restores them, the red bin there removes them
                                for good.
                            </>
                        }
                        bn={
                            <>
                                কেউ চলে গেলে তাঁকে এডিট করে{' '}
                                <Ui>Account status</Ui> <Ui>Inactive</Ui> করুন,
                                অথবা লাল ঝুড়ি চিহ্ন দিয়ে মুছে ফেলুন। মুছে ফেলা
                                ব্যবহারকারী <Ui>Deleted</Ui> ট্যাবে থাকেন:
                                তীরচিহ্ন তাঁকে ফিরিয়ে আনে, আর সেখানকার লাল
                                ঝুড়ি তাঁকে চিরতরে মুছে দেয়।
                            </>
                        }
                    >
                        <Shot
                            id="deleted"
                            en="The Deleted tab, with Restore and Permanently delete on each row."
                            bn="Deleted ট্যাব — প্রতিটি সারিতে ফিরিয়ে আনা ও চিরতরে মোছার বোতাম।"
                        />
                    </Step>
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Photo"
                        en="Optional. JPG, PNG or WebP, up to 2 MB. Without one, the user's initials are shown."
                        bn="ঐচ্ছিক। JPG, PNG বা WebP, সর্বোচ্চ ২ MB। ছবি না থাকলে ব্যবহারকারীর নামের আদ্যক্ষর দেখানো হয়।"
                    />
                    <Field
                        label="Username"
                        en="3–30 letters, digits, dots, dashes or underscores, starting with a letter or digit. Stored in lowercase, so JDoe and jdoe are the same name."
                        bn="৩–৩০টি অক্ষর, সংখ্যা, ডট, ড্যাশ বা আন্ডারস্কোর; শুরু হতে হবে অক্ষর বা সংখ্যা দিয়ে। ছোট হাতের অক্ষরে রাখা হয়, তাই JDoe ও jdoe একই নাম।"
                    />
                    <Field
                        label="Email address"
                        en="The second way to sign in, and where their mails go. Like the username and the employee code, it stays reserved by a deleted user until they are permanently deleted."
                        bn="সাইন ইনের দ্বিতীয় উপায়, আর তাঁর ইমেইলগুলো এখানেই যায়। ইউজারনেম ও এমপ্লয়ি কোডের মতো এটিও মুছে ফেলা ব্যবহারকারীর নামে আটকে থাকে, যতক্ষণ না তাঁকে চিরতরে মোছা হয়।"
                    />
                    <Field
                        label="Employee code"
                        en="Optional, and unique. The user can see it on their profile but not change it."
                        bn="ঐচ্ছিক, এবং প্রত্যেকের আলাদা। ব্যবহারকারী নিজের প্রোফাইলে এটি দেখতে পান, কিন্তু বদলাতে পারেন না।"
                    />
                    <Field
                        label="Account status"
                        en="Whether the account may sign in."
                        bn="অ্যাকাউন্টটি দিয়ে সাইন ইন করা যাবে কিনা।"
                    >
                        <EnumOptions
                            labels={RECORD_STATUS_LABELS}
                            explain={{
                                A: {
                                    en: 'Can sign in.',
                                    bn: 'সাইন ইন করতে পারেন।',
                                },
                                I: {
                                    en: 'Cannot sign in, and a session already open ends on their next click. The account and its history stay as they are.',
                                    bn: 'সাইন ইন করতে পারেন না, আর আগে থেকে খোলা সেশন তাঁর পরের ক্লিকেই শেষ হয়ে যায়। অ্যাকাউন্ট ও এর ইতিহাস যেমন ছিল তেমনই থাকে।',
                                },
                            }}
                        />
                    </Field>
                    <Field
                        label="Password"
                        en="Only on New user and Set a password. The checklist under the box ticks off each rule as you type; the save is refused until every line is met."
                        bn="কেবল New user ও Set a password-এ থাকে। টাইপ করার সাথে সাথে বক্সের নিচের চেকলিস্টে প্রতিটি নিয়ম টিক হয়ে যায়; সব শর্ত পূরণ না হওয়া পর্যন্ত সেভ হয় না।"
                    />
                    <Field
                        label="Email this user their username and password."
                        en="Ticked by default. The mail is queued and sent in the background; Sent Mails keeps a copy with the password masked."
                        bn="শুরুতে টিক দেওয়া থাকে। ইমেইলটি সারিতে রেখে পেছনে পাঠানো হয়; Sent Mails-এ যে কপি থাকে তাতে পাসওয়ার্ড লুকানো থাকে।"
                    />
                </Fields>

                <SubHeading en="The row actions" bn="সারির কাজগুলো" />
                <Fields>
                    <Field
                        label="Edit"
                        en="The pencil. Name, username, email, phone, code, photo and status. Roles and passwords have their own buttons."
                        bn="পেনসিল চিহ্ন। নাম, ইউজারনেম, ইমেইল, ফোন, কোড, ছবি ও স্ট্যাটাস। রোল ও পাসওয়ার্ডের জন্য আলাদা বোতাম আছে।"
                    />
                    <Field
                        label="Roles"
                        en="The shield. Replaces the user's roles. Disabled on your own row: you cannot change your own roles."
                        bn="ঢাল চিহ্ন। ব্যবহারকারীর রোলগুলো বদলে দেয়। আপনার নিজের সারিতে এটি বন্ধ থাকে: নিজের রোল নিজে বদলানো যায় না।"
                    />
                    <Field
                        label="Companies"
                        en="The building. Replaces the companies the user may work in. Disabled on your own row: you cannot change your own companies."
                        bn="ভবন চিহ্ন। ব্যবহারকারী যেসব কোম্পানিতে কাজ করতে পারেন সেগুলো বদলে দেয়। আপনার নিজের সারিতে এটি বন্ধ থাকে: নিজের কোম্পানি নিজে বদলানো যায় না।"
                    />
                    <Field
                        label="Set password"
                        en="The key. Sets a new password; the user gets a notification that it was changed."
                        bn="চাবি চিহ্ন। নতুন পাসওয়ার্ড দেয়; পাসওয়ার্ড বদলানো হয়েছে বলে ব্যবহারকারী একটি নোটিফিকেশন পান।"
                    />
                    <Field
                        label="Delete"
                        en="The red bin. Moves the user to Deleted; they can no longer sign in. Disabled on your own row and on the last active super admin."
                        bn="লাল ঝুড়ি চিহ্ন। ব্যবহারকারীকে Deleted-এ সরিয়ে দেয়; তিনি আর সাইন ইন করতে পারেন না। আপনার নিজের সারিতে এবং শেষ সক্রিয় সুপার অ্যাডমিনের সারিতে এটি বন্ধ থাকে।"
                    />
                </Fields>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="User created. Their sign-in details are being emailed to rahim@example.com."
                    en="Saved, and the welcome mail is on its way. Without the email tick box the message is just “User created.”"
                    bn="সেভ হয়েছে, আর স্বাগত ইমেইলটি পাঠানো হচ্ছে। ইমেইলের বক্সে টিক না থাকলে বার্তাটি শুধু “User created.”"
                />
                <Message
                    text="You cannot change your own roles. Ask another administrator."
                    en="Nobody raises or lowers their own access. Another administrator has to do it."
                    bn="কেউ নিজের প্রবেশাধিকার নিজে বাড়াতে বা কমাতে পারেন না। অন্য একজন অ্যাডমিনিস্ট্রেটরকে এটি করতে হবে।"
                />
                <Message
                    text="You cannot change your own companies. Ask another administrator."
                    en="Like roles, the companies you can reach are changed by someone else."
                    bn="রোলের মতোই, আপনি কোন কোম্পানিতে কাজ করবেন তা অন্য কেউ বদলান।"
                />
                <Message
                    text="You cannot deactivate your own account."
                    en="Setting yourself Inactive would lock you out. The same goes for deleting yourself: “You cannot delete your own account.”"
                    bn="নিজেকে Inactive করলে আপনি নিজেই আটকে যাবেন। নিজেকে মুছে ফেলার বেলাতেও একই কথা: “You cannot delete your own account.”"
                />
                <Message
                    text="This is the last active super admin. Grant the role to someone else first."
                    en="Nobody would be left who can administer everything. Give another active user the super-admin role, then try again."
                    bn="সবকিছু পরিচালনা করতে পারেন এমন কেউ আর থাকতেন না। আগে অন্য একজন সক্রিয় ব্যবহারকারীকে super-admin রোল দিন, তারপর আবার চেষ্টা করুন।"
                />
                <Message
                    text="Only a super admin may grant the super-admin role."
                    en="Only a super admin can hand out super-admin, or take it away (“Only a super admin may revoke the super-admin role.”)."
                    bn="কেবল একজন সুপার অ্যাডমিনই super-admin রোল দিতে বা সরিয়ে নিতে পারেন (“Only a super admin may revoke the super-admin role.”)।"
                />
                <Message
                    text="This account has been deactivated. Contact an administrator."
                    en="What an Inactive user sees on the sign-in page, once their password has matched."
                    bn="একজন Inactive ব্যবহারকারী পাসওয়ার্ড মিলে যাওয়ার পর সাইন-ইন পাতায় এই বার্তাটি দেখেন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Ticking a role is accepted at once and gives the user everything that role allows — nothing asks you to confirm. Check what a role grants on the Roles screen before you tick it, above all super-admin."
                    bn="কোনো রোলে টিক দিলে তা সাথে সাথে কার্যকর হয় এবং রোলটি যা যা অনুমতি দেয় তার সবই ব্যবহারকারী পেয়ে যান — নিশ্চিত করতে কিছুই জিজ্ঞেস করা হয় না। টিক দেওয়ার আগে Roles স্ক্রিনে দেখে নিন রোলটি কী অনুমতি দেয়, বিশেষ করে super-admin।"
                />
                <Callout
                    tone="info"
                    en="Deactivate rather than delete when someone may come back: an inactive account keeps everything and is one edit away from working again. Permanently delete only to free a username or email — it cannot be undone, though their audit history stays."
                    bn="কেউ ফিরে আসতে পারেন এমন হলে মুছে না ফেলে নিষ্ক্রিয় করুন: নিষ্ক্রিয় অ্যাকাউন্টে সবকিছু থেকে যায়, আর একটি এডিটেই আবার চালু হয়। চিরতরে মুছুন কেবল তখনই, যখন কোনো ইউজারনেম বা ইমেইল খালি করা দরকার — এটি আর ফেরানো যায় না, যদিও তাঁর অডিট ইতিহাস থেকে যায়।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.roles"
                    en="What each role grants, and making a new one."
                    bn="প্রতিটি রোল কী অনুমতি দেয়, এবং নতুন রোল তৈরি।"
                />
                <RelatedGuide
                    to="admin.sent-mails"
                    en="Checking that a welcome or password mail went out."
                    bn="স্বাগত বা পাসওয়ার্ডের ইমেইল গেছে কিনা যাচাই করা।"
                />
                <RelatedGuide
                    to="admin.audit-logs"
                    en="Who changed an account, and when."
                    bn="কে, কখন কোনো অ্যাকাউন্ট বদলেছেন।"
                />
            </GuideSection>
        </Guide>
    );
}
