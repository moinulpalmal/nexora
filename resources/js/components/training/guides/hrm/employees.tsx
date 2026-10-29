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
 * HRM → Employees (`hrm.employees.index`), with its New employee / Edit
 * dialog, the sign-in account it can open, the Deleted tab and the
 * spreadsheet import. Source: documentation/hrm.md §4.3.
 */
export default function EmployeesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The people the company you are working in employs: their employee ID, name, email, department, designation, mobiles, joining date and status. An employee can also be given a sign-in account from here, so they can use the application themselves — most never need one."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন সেখানে যাঁরা চাকরি করেন: তাঁদের এমপ্লয়ি আইডি, নাম, ইমেইল, বিভাগ, পদবি, মোবাইল, যোগদানের তারিখ ও অবস্থা। এখান থেকে কোনো কর্মচারীকে সাইন-ইন অ্যাকাউন্টও দেওয়া যায়, যাতে তিনি নিজে অ্যাপ্লিকেশন ব্যবহার করতে পারেন — বেশিরভাগ কর্মচারীর এর দরকার হয় না।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>hrm.employees.view</Ui> opens the list;{' '}
                            <Ui>hrm.employees.create</Ui>,{' '}
                            <Ui>hrm.employees.update</Ui> and{' '}
                            <Ui>hrm.employees.delete</Ui> add, edit and delete;{' '}
                            <Ui>hrm.employees.restore</Ui> and{' '}
                            <Ui>hrm.employees.force-delete</Ui> work on the
                            Deleted tab; <Ui>hrm.employees.import</Ui> loads
                            them from a spreadsheet.{' '}
                            <Ui>hrm.employees.create-account</Ui> shows the
                            Sign-in account box, and{' '}
                            <Ui>hrm.employees.view-compensation</Ui> shows the
                            monthly salary — without it the salary is neither
                            shown nor saved. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>hrm.employees.view</Ui> থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>hrm.employees.create</Ui>,{' '}
                            <Ui>hrm.employees.update</Ui> ও{' '}
                            <Ui>hrm.employees.delete</Ui> দিয়ে যোগ, সম্পাদনা ও
                            মোছা যায়; <Ui>hrm.employees.restore</Ui> ও{' '}
                            <Ui>hrm.employees.force-delete</Ui> Deleted ট্যাবে
                            কাজ করে; <Ui>hrm.employees.import</Ui> দিয়ে
                            স্প্রেডশিট থেকে আনা যায়।{' '}
                            <Ui>hrm.employees.create-account</Ui> থাকলে Sign-in
                            account অংশটি দেখা যায়, আর{' '}
                            <Ui>hrm.employees.view-compensation</Ui> থাকলে মাসিক
                            বেতন দেখা যায় — এটি না থাকলে বেতন দেখানোও হয় না,
                            সেভও হয় না। এর সাথে কাজ করার জন্য একটি কোম্পানিও
                            লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and every employee you add, belongs to the company shown there. The departments you can choose are that company's; designations are shared by every company. Add the departments and designations first — an employee needs one of each."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যত কর্মচারী যোগ করবেন সবাই সেখানে দেখানো কোম্পানির। যেসব বিভাগ বেছে নেওয়া যায় সেগুলো ওই কোম্পানির; পদবি সব কোম্পানির জন্য একই। আগে বিভাগ ও পদবি যোগ করে নিন — প্রতিটি কর্মচারীর একটি বিভাগ ও একটি পদবি লাগে।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open HRM → Employees. The description under the title names the company the list belongs to. A User badge beside a name means the employee can sign in."
                        bn="HRM → Employees খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। নামের পাশে User ব্যাজ থাকলে বুঝবেন কর্মচারীটি সাইন-ইন করতে পারেন।"
                    >
                        <Shot
                            id="overview"
                            en="The employees of the selected company, one of them an application user."
                            bn="বেছে নেওয়া কোম্পানির কর্মচারীরা, তাঁদের একজন অ্যাপ্লিকেশন ব্যবহারকারী।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New employee</Ui>, fill in the
                                employee ID, name, email, department and
                                designation, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New employee</Ui> চাপুন, এমপ্লয়ি আইডি, নাম,
                                ইমেইল, বিভাগ ও পদবি দিন, তারপর{' '}
                                <Ui>Save &amp; close</Ui> দিয়ে সেভ করুন — অথবা
                                আরও যোগ করতে <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New employee: the employee's details and the Sign-in account box."
                            bn="New employee: কর্মচারীর তথ্য ও Sign-in account অংশ।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকজনকে মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted employee waits on the Deleted tab, where they can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা কর্মচারী Deleted ট্যাবে থাকেন, সেখান থেকে তাঁকে ফিরিয়ে আনা বা চিরতরে মোছা যায় — একজন একজন করে, অথবা কয়েকজনে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>

                <SubHeading
                    en="Letting an employee sign in"
                    bn="কর্মচারীকে সাইন-ইন করতে দেওয়া"
                />
                <Steps>
                    <Step
                        en={
                            <>
                                In the <Ui>Sign-in account</Ui> box, tick{' '}
                                <Ui>Application user</Ui>, type a temporary
                                password twice and save. The account&apos;s
                                username is the employee ID in small letters.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Sign-in account</Ui> অংশে{' '}
                                <Ui>Application user</Ui>-এ টিক দিন, দুবার একটি
                                অস্থায়ী পাসওয়ার্ড লিখে সেভ করুন। অ্যাকাউন্টের
                                ইউজারনেম হবে ছোট হাতের অক্ষরে এমপ্লয়ি আইডি।
                            </>
                        }
                    >
                        <Shot
                            id="account"
                            en="Application user ticked: the temporary password and its checklist."
                            bn="Application user-এ টিক দেওয়া: অস্থায়ী পাসওয়ার্ড ও তার চেকলিস্ট।"
                        />
                    </Step>
                    <Step
                        en="The password is emailed to the employee, who must choose their own the first time they sign in. The account has no role, so it opens almost nothing: ask an administrator to give it a role in Admin → Users."
                        bn="পাসওয়ার্ডটি কর্মচারীকে ইমেইলে পাঠানো হয়, আর প্রথমবার সাইন-ইন করার সময় তাঁকে নিজের পাসওয়ার্ড বেছে নিতে হয়। অ্যাকাউন্টে কোনো রোল থাকে না, তাই প্রায় কিছুই খোলে না: Admin → Users-এ একটি রোল দিতে একজন অ্যাডমিনিস্ট্রেটরকে বলুন।"
                    />
                    <Step
                        en="To stop the account, untick Application user, make the employee Inactive, or delete them. The account is kept, inactive, and ticking the box again re-opens it with a new temporary password."
                        bn="অ্যাকাউন্ট বন্ধ করতে Application user-এর টিক তুলে দিন, কর্মচারীকে Inactive করুন, অথবা তাঁকে মুছে ফেলুন। অ্যাকাউন্টটি নিষ্ক্রিয় অবস্থায় রেখে দেওয়া হয়; আবার টিক দিলে নতুন অস্থায়ী পাসওয়ার্ড দিয়ে সেটি আবার চালু হয়।"
                    />
                </Steps>

                <SubHeading
                    en="Loading many employees from a spreadsheet"
                    bn="স্প্রেডশিট থেকে একসাথে অনেক কর্মচারী আনা"
                />
                <Steps>
                    <Step
                        en={
                            <>
                                Press <Ui>Template</Ui> for an empty workbook
                                with the right headings, fill one row per
                                employee — departments and designations by their
                                codes — then press <Ui>Import</Ui> and upload
                                it.
                            </>
                        }
                        bn={
                            <>
                                সঠিক শিরোনামসহ একটি খালি ওয়ার্কবুক পেতে{' '}
                                <Ui>Template</Ui> চাপুন, প্রতিটি কর্মচারীর জন্য
                                একটি সারি পূরণ করুন — বিভাগ ও পদবি তাদের কোড
                                দিয়ে — তারপর <Ui>Import</Ui> চেপে ফাইলটি আপলোড
                                করুন।
                            </>
                        }
                    >
                        <Shot
                            id="import"
                            en="The import dialog, ready for a workbook."
                            bn="ইমপোর্ট ডায়ালগ, ওয়ার্কবুক নেওয়ার জন্য প্রস্তুত।"
                        />
                    </Step>
                    <Step
                        en="Check which column feeds each field, and choose what happens to a row whose employee ID you already have: skip it or update it."
                        bn="কোন কলাম থেকে কোন ঘরের মান আসবে তা মিলিয়ে নিন, আর যে সারির এমপ্লয়ি আইডি আগে থেকেই আছে তার কী হবে তা বেছে নিন: বাদ দেওয়া হবে, নাকি হালনাগাদ করা হবে।"
                    />
                    <Step
                        en="Review shows what Import would create, update, skip and reject before anything is written. Import writes the good rows; rejected rows are listed with the reason and can be downloaded, corrected and uploaded again. An import never opens sign-in accounts."
                        bn="কিছু লেখার আগেই Review দেখায় Import কী তৈরি, হালনাগাদ, বাদ ও প্রত্যাখ্যান করবে। Import ঠিক সারিগুলো লেখে; প্রত্যাখ্যাত সারিগুলো কারণসহ তালিকায় দেখায়, সেগুলো ডাউনলোড করে ঠিক করে আবার আপলোড করা যায়। ইমপোর্ট কখনো সাইন-ইন অ্যাকাউন্ট খোলে না।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Employee ID"
                        en="Up to 30 letters and digits, optionally joined by hyphens or underscores, such as E1001 or HQ-042. Saved in capitals. No two employees may share it in any company — a deleted one included."
                        bn="সর্বোচ্চ ৩০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন E1001 বা HQ-042। বড় হাতের অক্ষরে সেভ হয়। কোনো কোম্পানিতেই দুজন কর্মচারীর একই আইডি হতে পারে না — মুছে ফেলা কর্মচারীও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="The employee's full name, up to 255 characters."
                        bn="কর্মচারীর পুরো নাম, সর্বোচ্চ ২৫৫ অক্ষর।"
                    />
                    <Field
                        label="Email"
                        en="Required, and used by no other employee in any company, a deleted one included."
                        bn="আবশ্যক, এবং কোনো কোম্পানির অন্য কোনো কর্মচারী — মুছে ফেলা কেউও — এটি ব্যবহার করতে পারবেন না।"
                    />
                    <Field label="Joining date" en="Optional." bn="ঐচ্ছিক।" />
                    <Field
                        label="Department"
                        en="An active department of the selected company. An employee keeps a department that was made inactive after they joined it."
                        bn="বেছে নেওয়া কোম্পানির একটি সক্রিয় বিভাগ। যোগ দেওয়ার পরে বিভাগটি নিষ্ক্রিয় হলেও কর্মচারী সেই বিভাগেই থাকেন।"
                    />
                    <Field
                        label="Designation"
                        en="An active designation; the same list for every company."
                        bn="একটি সক্রিয় পদবি; সব কোম্পানির জন্য একই তালিকা।"
                    />
                    <Field
                        label="Official mobile / Personal mobile"
                        en="Optional Bangladeshi mobile numbers. Spaces, hyphens and +880 are tidied away, so +880 1712-345678 is saved as 01712345678."
                        bn="ঐচ্ছিক বাংলাদেশি মোবাইল নম্বর। ফাঁকা জায়গা, হাইফেন ও +880 বাদ দিয়ে গুছিয়ে রাখা হয়, তাই +880 1712-345678 সেভ হয় 01712345678 হিসেবে।"
                    />
                    <Field
                        label="Monthly salary (BDT)"
                        en="Only with hrm.employees.view-compensation. Zero or more, up to two decimals."
                        bn="শুধু hrm.employees.view-compensation থাকলে দেখা যায়। শূন্য বা তার বেশি, সর্বোচ্চ দুই দশমিক ঘর।"
                    />
                    <Field
                        label="Status"
                        en="Whether the employee is current:"
                        bn="কর্মচারী বর্তমানে কর্মরত কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Current. Their sign-in account, if any, works.',
                            bn: 'বর্তমানে কর্মরত। সাইন-ইন অ্যাকাউন্ট থাকলে সেটি কাজ করে।',
                        },
                        I: {
                            en: 'Kept on record; their sign-in account, if any, stops.',
                            bn: 'রেকর্ডে রাখা আছে; সাইন-ইন অ্যাকাউন্ট থাকলে সেটি বন্ধ হয়ে যায়।',
                        },
                    }}
                />
                <Fields>
                    <Field
                        label="Application user"
                        en="Ticked, the employee has a working sign-in account. Every save copies the name, email, official mobile and employee ID onto it."
                        bn="টিক দেওয়া থাকলে কর্মচারীর একটি চালু সাইন-ইন অ্যাকাউন্ট আছে। প্রতিবার সেভ করলে নাম, ইমেইল, অফিসিয়াল মোবাইল ও এমপ্লয়ি আইডি অ্যাকাউন্টে কপি হয়।"
                    />
                    <Field
                        label="Temporary password"
                        en="Only when an account is being opened or re-opened. It must meet every rule in the checklist; the employee replaces it at first sign-in."
                        bn="শুধু অ্যাকাউন্ট খোলা বা আবার চালু করার সময় দেখা যায়। চেকলিস্টের সব শর্ত পূরণ করতে হবে; প্রথম সাইন-ইনে কর্মচারী এটি বদলে নেন।"
                    />
                </Fields>
                <Bullets>
                    <Bullet
                        en="There is no company field: an employee always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: কর্মচারী সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যান।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="Employee created. Their sign-in details are being emailed to rafiq.islam@example.com."
                    en="Saved, and the account was opened. The welcome mail with the temporary password is on its way."
                    bn="সেভ হয়েছে এবং অ্যাকাউন্ট খোলা হয়েছে। অস্থায়ী পাসওয়ার্ডসহ স্বাগত ইমেইলটি পাঠানো হচ্ছে।"
                />
                <Message
                    text='The employee ID "E1001" is already taken.'
                    en="Another employee, in this or another company, has the ID."
                    bn="এই বা অন্য কোনো কোম্পানির আরেকজন কর্মচারীর এই আইডি আছে।"
                />
                <Message
                    text='The employee ID "E1001" belongs to a deleted employee. Restore them from the Deleted tab instead.'
                    en="A deleted employee still holds the ID. Restore them rather than adding the person twice."
                    bn="মুছে ফেলা একজন কর্মচারীর এখনো এই আইডিটি আছে। একই মানুষকে দুবার যোগ না করে তাঁকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text='Another employee already uses "rafiq.islam@example.com".'
                    en="Two employees cannot share an email, whatever their companies."
                    bn="কোম্পানি যা-ই হোক, দুজন কর্মচারীর একই ইমেইল হতে পারে না।"
                />
                <Message
                    text="Choose an active department of this company."
                    en="The department is inactive, deleted, or belongs to another company."
                    bn="বিভাগটি নিষ্ক্রিয়, মুছে ফেলা, অথবা অন্য কোম্পানির।"
                />
                <Message
                    text="Enter a Bangladeshi mobile number, e.g. 01712345678 or +8801712345678."
                    en="The number is not an 11-digit Bangladeshi mobile starting 013–019."
                    bn="নম্বরটি ০১৩–০১৯ দিয়ে শুরু হওয়া ১১ অঙ্কের বাংলাদেশি মোবাইল নম্বর নয়।"
                />
                <Message
                    text="An application user needs an employee ID of at least 3 characters — it becomes their username."
                    en="Lengthen the ID, or leave Application user unticked."
                    bn="আইডিটি লম্বা করুন, অথবা Application user-এ টিক দেবেন না।"
                />
                <Message
                    text='The username "e1001" is already used by another sign-in account.'
                    en="An account in Admin → Users already has that username. Existing accounts are not linked from here."
                    bn="Admin → Users-এ একটি অ্যাকাউন্টের ইতিমধ্যে এই ইউজারনেম আছে। এখান থেকে আগের অ্যাকাউন্টের সাথে যুক্ত করা যায় না।"
                />
                <Message
                    text="Another sign-in account already uses this email."
                    en="An account in Admin → Users has the email. Use another, or ask an administrator."
                    bn="Admin → Users-এর একটি অ্যাকাউন্টে এই ইমেইল আছে। অন্য ইমেইল দিন, অথবা একজন অ্যাডমিনিস্ট্রেটরকে জিজ্ঞেস করুন।"
                />
                <Message
                    text="You cannot deactivate your own account."
                    en="This employee's account is the one you are signed in with, so it cannot be stopped from here."
                    bn="এই কর্মচারীর অ্যাকাউন্ট দিয়েই আপনি সাইন-ইন করে আছেন, তাই এখান থেকে এটি বন্ধ করা যায় না।"
                />
                <Message
                    text="Employee restored. Their sign-in account stays inactive until you tick Application user again."
                    en="The employee is back; open the form and tick Application user if they should sign in again."
                    bn="কর্মচারী ফিরে এসেছেন; তাঁর আবার সাইন-ইন করার দরকার হলে ফর্ম খুলে Application user-এ টিক দিন।"
                />
                <Message
                    text="Select a company in the header before adding an employee."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
                <Message
                    text='No active department of this company has the code "PROD".'
                    en="An import row names a department code the selected company does not have, or one that is inactive."
                    bn="ইমপোর্টের একটি সারিতে এমন বিভাগ কোড আছে যা বেছে নেওয়া কোম্পানিতে নেই, অথবা নিষ্ক্রিয়।"
                />
                <Message
                    text='The employee ID "E1001" belongs to an employee of another company.'
                    en="An import row would move an employee between companies, which an import never does."
                    bn="ইমপোর্টের একটি সারি একজন কর্মচারীকে এক কোম্পানি থেকে আরেক কোম্পানিতে সরিয়ে দিত, যা ইমপোর্ট কখনো করে না।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding employees while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving or importing."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় কর্মচারী যোগ করলে তাঁরা সেই কোম্পানিতেই চলে যান — কোনো সতর্কবার্তা আসে না। সেভ বা ইমপোর্টের আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="danger"
                    en="Changing an application user's employee ID or email changes their username and sign-in email too. Tell them before you save."
                    bn="অ্যাপ্লিকেশন ব্যবহারকারীর এমপ্লয়ি আইডি বা ইমেইল বদলালে তাঁর ইউজারনেম ও সাইন-ইনের ইমেইলও বদলে যায়। সেভ করার আগে তাঁকে জানিয়ে দিন।"
                />
                <Callout
                    tone="warn"
                    en="Choosing Update on an import overwrites the matched employees' details with the sheet's. Use Skip when the sheet is only meant to add new ones."
                    bn="ইমপোর্টে Update বেছে নিলে মিলে যাওয়া কর্মচারীদের তথ্য শিটের মান দিয়ে বদলে যায়। শিট দিয়ে শুধু নতুন কর্মচারী যোগ করতে চাইলে Skip বেছে নিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="hrm.departments"
                    en="The departments an employee is placed in."
                    bn="যেসব বিভাগে কর্মচারীদের রাখা হয়।"
                />
                <RelatedGuide
                    to="hrm.designations"
                    en="The job titles an employee holds."
                    bn="কর্মচারীদের পদবিগুলো।"
                />
                <RelatedGuide
                    to="admin.users"
                    en="Giving an employee's account a role."
                    bn="কর্মচারীর অ্যাকাউন্টে রোল দেওয়া।"
                />
                <RelatedGuide
                    to="security.edit"
                    en="Where a new account chooses its own password."
                    bn="যেখানে নতুন অ্যাকাউন্ট নিজের পাসওয়ার্ড বেছে নেয়।"
                />
            </GuideSection>
        </Guide>
    );
}
