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
 * Admin → Countries (`admin.countries.index`), with its New country / Edit
 * dialog and the Deleted tab. Source: documentation/admin.md §11.
 */
export default function CountriesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The list of countries every company shares. It comes filled with the standard ISO list — about 250 countries and territories, each with its codes, dial code, currency and nationality — so normally you only correct a detail, or deactivate a country you never want offered."
                    bn="সব কোম্পানির জন্য একই দেশের তালিকা। এটি আগে থেকেই আন্তর্জাতিক ISO তালিকা দিয়ে পূর্ণ থাকে — প্রায় ২৫০টি দেশ ও অঞ্চল, প্রতিটির কোড, ডায়াল কোড, মুদ্রা ও জাতীয়তাসহ — তাই সাধারণত শুধু কোনো তথ্য সংশোধন করবেন, অথবা যে দেশ কখনো দেখাতে চান না সেটিকে নিষ্ক্রিয় করবেন।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.countries.view</Ui> opens the list;{' '}
                            <Ui>admin.countries.create</Ui>,{' '}
                            <Ui>admin.countries.update</Ui> and{' '}
                            <Ui>admin.countries.delete</Ui> add, edit and
                            delete. <Ui>admin.countries.restore</Ui> and{' '}
                            <Ui>admin.countries.force-delete</Ui> work on the
                            Deleted tab.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.countries.view</Ui> থাকলে তালিকা খোলা
                            যায়; <Ui>admin.countries.create</Ui>,{' '}
                            <Ui>admin.countries.update</Ui> ও{' '}
                            <Ui>admin.countries.delete</Ui> দিয়ে যোগ, সম্পাদনা
                            ও মোছা যায়। <Ui>admin.countries.restore</Ui> ও{' '}
                            <Ui>admin.countries.force-delete</Ui> Deleted ট্যাবে
                            কাজ করে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Admin → Countries. Each row shows the two- and three-letter ISO codes, the name, the dial code, the currency, the nationality and whether the country is active. Type in the boxes under the headings to find one — +88 in Dial code finds Bangladesh."
                        bn="Admin → Countries খুলুন। প্রতিটি সারিতে থাকে দুই ও তিন অক্ষরের ISO কোড, নাম, ডায়াল কোড, মুদ্রা, জাতীয়তা এবং দেশটি সক্রিয় কি না। খুঁজতে শিরোনামের নিচের বক্সে লিখুন — Dial code-এ +88 লিখলে বাংলাদেশ পাওয়া যায়।"
                    >
                        <Shot
                            id="overview"
                            en="The seeded countries, each with its codes, currency and status."
                            bn="আগে থেকে যোগ করা দেশগুলো, প্রতিটির কোড, মুদ্রা ও অবস্থাসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                To add a country that is missing, press{' '}
                                <Ui>New country</Ui>, enter the name and both
                                ISO codes, fill in whatever else you know, and
                                save with <Ui>Save &amp; close</Ui>.
                            </>
                        }
                        bn={
                            <>
                                তালিকায় নেই এমন দেশ যোগ করতে{' '}
                                <Ui>New country</Ui> চাপুন, নাম ও দুটি ISO কোড
                                দিন, বাকি যা জানেন তা পূরণ করুন, তারপর{' '}
                                <Ui>Save &amp; close</Ui> দিয়ে সেভ করুন।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New country: name, ISO codes, numeric code, dial code, currency, nationality and status."
                            bn="New country: নাম, ISO কোড, সংখ্যাসূচক কোড, ডায়াল কোড, মুদ্রা, জাতীয়তা ও অবস্থা।"
                        />
                    </Step>
                    <Step
                        en="To stop offering a country, edit it and set its status to Inactive. It stays on the list and on every record that already uses it."
                        bn="কোনো দেশ আর দেখাতে না চাইলে এটি সম্পাদনা করে অবস্থা Inactive করুন। এটি তালিকায় এবং যেসব রেকর্ডে আগে থেকে ব্যবহৃত হয়েছে সেখানে থেকে যাবে।"
                    />
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted country waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা দেশ Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    >
                        <Shot
                            id="deleted"
                            en="The Deleted tab, with restore and permanent delete."
                            bn="Deleted ট্যাব — ফিরিয়ে আনা ও চিরতরে মোছার বোতামসহ।"
                        />
                    </Step>
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Name"
                        en="The country's name, up to 100 characters. Extra spaces are removed. No two countries may share a name, whatever the capitals — a deleted country included."
                        bn="দেশের নাম, সর্বোচ্চ ১০০ অক্ষর। বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট হাতের অক্ষর যা-ই হোক, দুটি দেশের একই নাম হতে পারে না — মুছে ফেলা দেশও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="ISO code (2)"
                        en="Exactly two letters, such as BD. Saved in capitals and unique in the same way as the name."
                        bn="ঠিক দুটি অক্ষর, যেমন BD। বড় হাতের অক্ষরে সেভ হয় এবং নামের মতোই অনন্য হতে হবে।"
                    />
                    <Field
                        label="ISO code (3)"
                        en="Exactly three letters, such as BGD. Saved in capitals and unique in the same way."
                        bn="ঠিক তিনটি অক্ষর, যেমন BGD। বড় হাতের অক্ষরে সেভ হয় এবং একইভাবে অনন্য হতে হবে।"
                    />
                    <Field
                        label="Numeric code"
                        en="Optional. The three-digit ISO number, such as 050 — keep the leading zero."
                        bn="ঐচ্ছিক। তিন অঙ্কের ISO নম্বর, যেমন 050 — শুরুর শূন্যটি রাখুন।"
                    />
                    <Field
                        label="Dial code"
                        en="Optional. A plus sign and digits, such as +880; places that share a code add an area part, such as +1-684. Several countries may share one."
                        bn="ঐচ্ছিক। যোগ চিহ্ন ও অঙ্ক, যেমন +880; যেসব স্থান একই কোড ভাগ করে তাদের সাথে এলাকার অংশ যোগ হয়, যেমন +1-684। একাধিক দেশ একই কোড ব্যবহার করতে পারে।"
                    />
                    <Field
                        label="Currency"
                        en="Optional. The three-letter currency code, such as BDT. Several countries may share one, such as EUR."
                        bn="ঐচ্ছিক। তিন অক্ষরের মুদ্রা কোড, যেমন BDT। একাধিক দেশ একই মুদ্রা ব্যবহার করতে পারে, যেমন EUR।"
                    />
                    <Field
                        label="Nationality"
                        en="Optional. What a person from the country is called, such as Bangladeshi."
                        bn="ঐচ্ছিক। দেশের মানুষকে কী বলা হয়, যেমন Bangladeshi।"
                    />
                    <Field
                        label="Status"
                        en="Whether the country is offered:"
                        bn="দেশটি দেখানো হবে কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Offered wherever a country is chosen.',
                            bn: 'যেখানে দেশ বেছে নিতে হয় সেখানে দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record, but not offered for new entries.',
                            bn: 'রেকর্ডে থাকে, কিন্তু নতুন এন্ট্রির জন্য দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="Nothing in the application points at a country yet, so any country can be deleted."
                        bn="অ্যাপ্লিকেশনের কোনো রেকর্ড এখনো কোনো দেশের সাথে যুক্ত নয়, তাই যেকোনো দেশ মোছা যায়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='A country named "Bangladesh" already exists.'
                    en="Another country has this name, perhaps in other capitals. Edit that country instead."
                    bn="অন্য কোনো দেশের এই নাম আছে, হয়তো ভিন্ন বড়-ছোট অক্ষরে। তার বদলে সেই দেশটি সম্পাদনা করুন।"
                />
                <Message
                    text='The ISO code "BD" is already taken.'
                    en="Another country uses this code. Check the code, or edit that country."
                    bn="অন্য কোনো দেশ এই কোড ব্যবহার করছে। কোডটি যাচাই করুন, অথবা সেই দেশটি সম্পাদনা করুন।"
                />
                <Message
                    text='The ISO code "BD" belongs to a deleted country. Restore it from the Deleted tab instead.'
                    en="A deleted country still holds the code (or the name). Restore that one rather than making a second."
                    bn="মুছে ফেলা একটি দেশের এখনো এই কোড (বা নাম) আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text='Start with + and digits, e.g. "+880" or "+1-684".'
                    en="The dial code is not in the expected shape. Type the plus sign first."
                    bn="ডায়াল কোডটি সঠিক আকারে নেই। প্রথমে যোগ চিহ্ন লিখুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="Deactivate a country rather than deleting it. A country deleted for good is added back the next time the database seeder runs; an inactive one stays as you left it."
                    bn="দেশ মুছে না ফেলে নিষ্ক্রিয় করুন। চিরতরে মোছা দেশ পরের বার ডেটাবেস seeder চালালে আবার যোগ হয়ে যায়; নিষ্ক্রিয় দেশ যেমন রেখেছেন তেমনই থাকে।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.companies"
                    en="Your organization's companies — the other master list Admin keeps."
                    bn="আপনার প্রতিষ্ঠানের কোম্পানিগুলো — Admin-এর আরেকটি মূল তালিকা।"
                />
            </GuideSection>
        </Guide>
    );
}
