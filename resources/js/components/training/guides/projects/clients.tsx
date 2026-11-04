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
    Ui,
} from '@/components/training/vocabulary';
import { RECORD_STATUS_LABELS } from '@/lib/enums';

/**
 * Projects → Settings → Clients (`projects.settings.clients.index`), with its New client / Edit
 * dialog and the Deleted tab. Source: documentation/projects.md §4 (Clients).
 */
export default function ClientsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The clients of the company you are working in — the organizations you run projects for and, later, send invoices to. It is the one customer list for the whole application. Each company keeps its own list, so two companies may both have a client coded ACME."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার ক্লায়েন্টরা — যেসব প্রতিষ্ঠানের জন্য আপনি প্রকল্প চালান এবং পরে ইনভয়েস পাঠাবেন। পুরো অ্যাপ্লিকেশনে গ্রাহকের তালিকা এই একটিই। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে, তাই দুটি কোম্পানিতেই ACME কোডের ক্লায়েন্ট থাকতে পারে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.clients.view</Ui> opens the
                            list; <Ui>projects.settings.clients.create</Ui>,{' '}
                            <Ui>projects.settings.clients.update</Ui> and{' '}
                            <Ui>projects.settings.clients.delete</Ui> add, edit
                            and delete;{' '}
                            <Ui>projects.settings.clients.restore</Ui> and{' '}
                            <Ui>projects.settings.clients.force-delete</Ui> work
                            on the Deleted tab. The project-manager role holds
                            them all. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.clients.view</Ui> থাকলে তালিকা
                            খোলা যায়; <Ui>projects.settings.clients.create</Ui>
                            , <Ui>projects.settings.clients.update</Ui> ও{' '}
                            <Ui>projects.settings.clients.delete</Ui> দিয়ে যোগ,
                            সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.settings.clients.restore</Ui> ও{' '}
                            <Ui>projects.settings.clients.force-delete</Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোলের কাছে
                            এর সবগুলোই আছে। এর সাথে কাজ করার জন্য একটি কোম্পানিও
                            লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and every client you add, belongs to the company shown there. If the screen says to select a company, choose one from the switcher; if it says you have none, ask an administrator to give you one."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যে ক্লায়েন্ট যোগ করবেন সবই সেখানে দেখানো কোম্পানির। স্ক্রিনে কোম্পানি বেছে নিতে বললে সুইচার থেকে একটি বেছে নিন; যদি বলে আপনার কোনো কোম্পানি নেই, তাহলে একজন অ্যাডমিনিস্ট্রেটরকে একটি দিতে বলুন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Clients. The description under the title names the company the list belongs to. Search by code, name, contact person, phone or email in the row under the headings."
                        bn="Projects → Settings → Clients খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। শিরোনামের নিচের সারিতে কোড, নাম, যোগাযোগকারী, ফোন বা ইমেইল দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The clients of the selected company, with the company switcher in the header."
                            bn="বেছে নেওয়া কোম্পানির ক্লায়েন্টরা, হেডারে কোম্পানি সুইচারসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New client</Ui>, enter at least a code
                                and a name, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New client</Ui> চাপুন, অন্তত একটি কোড ও নাম
                                দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে সেভ
                                করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New client: code, name, contact details, status, address and description."
                            bn="New client: কোড, নাম, যোগাযোগের তথ্য, অবস্থা, ঠিকানা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted client waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা ক্লায়েন্ট Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as ACME or ACME-01. Saved in capitals. No two clients of the company may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন ACME বা ACME-01। বড় হাতের অক্ষরে সেভ হয়। কোম্পানির দুটি ক্লায়েন্টের একই কোড হতে পারে না — মুছে ফেলা ক্লায়েন্টও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 150 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted client included."
                        bn="সর্বোচ্চ ১৫০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা ক্লায়েন্টও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Contact person"
                        en="Optional. The person you deal with at the client."
                        bn="ঐচ্ছিক। ক্লায়েন্টের যে ব্যক্তির সাথে আপনি যোগাযোগ করেন।"
                    />
                    <Field
                        label="Phone"
                        en="Optional, up to 30 characters: digits, spaces and + ( ) -. Any country's format is accepted."
                        bn="ঐচ্ছিক, সর্বোচ্চ ৩০ অক্ষর: সংখ্যা, ফাঁকা জায়গা ও + ( ) -। যেকোনো দেশের ফরম্যাট চলবে।"
                    />
                    <Field
                        label="Email"
                        en="Optional. Saved in small letters. Two clients may share an email — a group's billing address, for example."
                        bn="ঐচ্ছিক। ছোট হাতের অক্ষরে সেভ হয়। দুটি ক্লায়েন্টের একই ইমেইল থাকতে পারে — যেমন কোনো গ্রুপের বিলিং ঠিকানা।"
                    />
                    <Field
                        label="Address"
                        en="Optional, up to 500 characters."
                        bn="ঐচ্ছিক, সর্বোচ্চ ৫০০ অক্ষর।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes about the client. Shown in the form, not in the list."
                        bn="ক্লায়েন্ট সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the client is in use:"
                        bn="ক্লায়েন্টটি ব্যবহারে আছে কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'In use, and offered for new projects.',
                            bn: 'ব্যবহারে আছে, নতুন প্রকল্পের জন্য দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record but no longer offered.',
                            bn: 'রেকর্ডে রাখা আছে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: a client always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: ক্লায়েন্ট সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: clients are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: ক্লায়েন্ট একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "ACME" is already taken in this company.'
                    en="Another client of this company uses the code, perhaps in small letters. The same code in another company is fine."
                    bn="এই কোম্পানির অন্য একটি ক্লায়েন্ট কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে। অন্য কোম্পানিতে একই কোড থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A client named "Acme Trading Ltd." already exists in this company.'
                    en="The name is taken in this company, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, এই কোম্পানিতে নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted client in this company is named "Acme Trading Ltd.". Restore it from the Deleted tab instead.'
                    en="A deleted client still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি ক্লায়েন্টের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Use digits, spaces and + ( ) - only."
                    en="The phone holds a character a phone number cannot have, such as a letter."
                    bn="ফোন নম্বরে এমন অক্ষর আছে যা ফোন নম্বরে থাকতে পারে না, যেমন কোনো বর্ণ।"
                />
                <Message
                    text="Select a company in the header before adding a client."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding clients while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় ক্লায়েন্ট যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ করার আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="A company that still has clients — deleted ones included — cannot be deleted. Make the company Inactive instead, or delete its clients for good first."
                    bn="যে কোম্পানিতে এখনো ক্লায়েন্ট আছে — মুছে ফেলা ক্লায়েন্টসহ — সেটি মোছা যায় না। তার বদলে কোম্পানিটিকে Inactive করুন, অথবা আগে তার ক্লায়েন্টদের চিরতরে মুছুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.companies"
                    en="The companies clients belong to."
                    bn="যেসব কোম্পানির অধীনে ক্লায়েন্টরা থাকে।"
                />
                <RelatedGuide
                    to="admin.users"
                    en="Giving someone a company to work in."
                    bn="কাউকে কাজের জন্য একটি কোম্পানি দেওয়া।"
                />
            </GuideSection>
        </Guide>
    );
}
