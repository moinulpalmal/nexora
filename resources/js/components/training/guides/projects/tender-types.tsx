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
 * Projects → Settings → Tender Types (`projects.settings.tender-types.index`), with its New
 * tender type / Edit dialog and the Deleted tab. Source:
 * documentation/projects.md §4 (Tender types).
 */
export default function TenderTypesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The ways a project can be procured — open tendering, limited tendering, request for quotation, direct procurement and the like. There is one list for the whole group: every company sees the same tender types, so an edit or a delete shows in all of them."
                    bn="একটি প্রকল্প যেসব পদ্ধতিতে সংগ্রহ করা হতে পারে — উন্মুক্ত দরপত্র, সীমিত দরপত্র, কোটেশন অনুরোধ, সরাসরি ক্রয় ইত্যাদি। পুরো গ্রুপের জন্য তালিকা একটিই: প্রতিটি কোম্পানি একই দরপত্রের ধরন দেখে, তাই কোনো সম্পাদনা বা মোছা সবগুলো কোম্পানিতেই দেখা যায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.tender-types.view</Ui> opens
                            the list;{' '}
                            <Ui>projects.settings.tender-types.create</Ui>,{' '}
                            <Ui>projects.settings.tender-types.update</Ui> and{' '}
                            <Ui>projects.settings.tender-types.delete</Ui> add,
                            edit and delete;{' '}
                            <Ui>projects.settings.tender-types.restore</Ui> and{' '}
                            <Ui>projects.settings.tender-types.force-delete</Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            may view, add and edit, but not delete or restore:
                            the list is shared, so removing a tender type is
                            left to the super admin. No company needs to be
                            selected.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.tender-types.view</Ui> থাকলে
                            তালিকা খোলা যায়;{' '}
                            <Ui>projects.settings.tender-types.create</Ui>,{' '}
                            <Ui>projects.settings.tender-types.update</Ui> ও{' '}
                            <Ui>projects.settings.tender-types.delete</Ui> দিয়ে
                            যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.settings.tender-types.restore</Ui> ও{' '}
                            <Ui>projects.settings.tender-types.force-delete</Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোল দেখতে,
                            যোগ করতে ও সম্পাদনা করতে পারে, কিন্তু মুছতে বা
                            ফিরিয়ে আনতে পারে না: তালিকাটি সবার, তাই দরপত্রের
                            ধরন মোছার কাজ সুপার অ্যাডমিনের। কোনো কোম্পানি বেছে
                            নেওয়ার দরকার নেই।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Agree the codes with the other companies first: the list is shared, so a code chosen here is the code everyone uses."
                    bn="আগে অন্য কোম্পানিগুলোর সাথে কোডগুলো ঠিক করে নিন: তালিকাটি সবার, তাই এখানে বেছে নেওয়া কোডটিই সবাই ব্যবহার করবে।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Tender Types. Search by code or name in the row under the headings, or pick a status."
                        bn="Projects → Settings → Tender Types খুলুন। শিরোনামের নিচের সারিতে কোড বা নাম দিয়ে খুঁজুন, অথবা একটি অবস্থা বেছে নিন।"
                    >
                        <Shot
                            id="overview"
                            en="The tender types every company shares."
                            bn="সব কোম্পানির একই দরপত্রের ধরনগুলো।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New tender type</Ui>, enter a code and
                                a name, and save with <Ui>Save &amp; close</Ui>{' '}
                                — or <Ui>Save &amp; add another</Ui> to keep
                                going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New tender type</Ui> চাপুন, একটি কোড ও নাম
                                দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে সেভ
                                করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New tender type: code, name and status."
                            bn="New tender type: কোড, নাম ও অবস্থা।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted tender type waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা দরপত্রের ধরন Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as OTM or RFQ-2. Saved in capitals. No two tender types may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন OTM বা RFQ-2। বড় হাতের অক্ষরে সেভ হয়। দুটি দরপত্রের ধরনের একই কোড হতে পারে না — মুছে ফেলাটিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique whatever the capitals, a deleted tender type included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, অনন্য হতে হবে — মুছে ফেলা দরপত্রের ধরনও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the tender type is in use:"
                        bn="দরপত্রের ধরনটি ব্যবহারে আছে কি না:"
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
                        en="There is no company field: every company shares the same tender types."
                        bn="কোনো কোম্পানি ঘর নেই: সব কোম্পানি একই দরপত্রের ধরন ব্যবহার করে।"
                    />
                    <Bullet
                        en="There is no spreadsheet import, and a new installation starts with an empty list: tender types are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই, এবং নতুন ইনস্টলেশনে তালিকা খালি থাকে: দরপত্রের ধরন একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "OTM" is already taken.'
                    en="Another tender type uses the code, perhaps in small letters."
                    bn="অন্য একটি দরপত্রের ধরন কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A tender type named "Open Tendering Method" already exists.'
                    en="The name is taken, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted tender type is named "Open Tendering Method". Restore it from the Deleted tab instead.'
                    en="A deleted tender type still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি দরপত্রের ধরনের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text='Use letters and digits, optionally joined by single hyphens or underscores, e.g. "OTM".'
                    en="The code holds a space or a symbol. Use letters and digits only, joined by single hyphens or underscores."
                    bn="কোডে ফাঁকা জায়গা বা কোনো চিহ্ন আছে। শুধু অক্ষর ও সংখ্যা ব্যবহার করুন, একক হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="Renaming, deactivating or deleting a tender type changes it for every company, not only yours. If one company stops using a method, leave it Active for the others."
                    bn="দরপত্রের ধরনের নাম বদলানো, নিষ্ক্রিয় করা বা মোছা শুধু আপনার নয়, সব কোম্পানির জন্যই তা বদলে দেয়। কোনো একটি কোম্পানি একটি পদ্ধতি ব্যবহার বন্ধ করলে অন্যদের জন্য সেটি Active রেখে দিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.settings.clients"
                    en="The clients projects are run for."
                    bn="যেসব ক্লায়েন্টের জন্য প্রকল্প চালানো হয়।"
                />
            </GuideSection>
        </Guide>
    );
}
