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
 * HRM → Designations (`hrm.designations.index`), with its New designation /
 * Edit dialog, the Deleted tab and the spreadsheet import. Designations are
 * group-wide: one list every company shares. Source: documentation/hrm.md §4.
 */
export default function DesignationsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The job titles used across your organization — Operator, Supervisor, Manager and so on. Employees will point at them. There is one list for every company: a designation added here is offered in all of them, whichever company is selected in the header."
                    bn="প্রতিষ্ঠানজুড়ে ব্যবহৃত পদবিগুলো — Operator, Supervisor, Manager ইত্যাদি। কর্মীরা এগুলোর সাথে যুক্ত থাকবে। সব কোম্পানির জন্য একটিই তালিকা: এখানে যোগ করা পদবি হেডারে যে কোম্পানিই বেছে নেওয়া থাকুক, সবগুলোতেই দেখানো হয়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>hrm.designations.view</Ui> opens the list;{' '}
                            <Ui>hrm.designations.create</Ui> and{' '}
                            <Ui>hrm.designations.update</Ui> add and edit;{' '}
                            <Ui>hrm.designations.delete</Ui>,{' '}
                            <Ui>hrm.designations.restore</Ui> and{' '}
                            <Ui>hrm.designations.force-delete</Ui> delete and
                            work on the Deleted tab;{' '}
                            <Ui>hrm.designations.import</Ui> loads them from a
                            spreadsheet. HR officers can add, edit and import,
                            but deleting is left to an administrator because it
                            affects every company.
                        </>
                    }
                    bn={
                        <>
                            <Ui>hrm.designations.view</Ui> থাকলে তালিকা খোলা
                            যায়; <Ui>hrm.designations.create</Ui> ও{' '}
                            <Ui>hrm.designations.update</Ui> দিয়ে যোগ ও
                            সম্পাদনা করা যায়; <Ui>hrm.designations.delete</Ui>,{' '}
                            <Ui>hrm.designations.restore</Ui> ও{' '}
                            <Ui>hrm.designations.force-delete</Ui> দিয়ে মোছা ও
                            Deleted ট্যাবে কাজ করা যায়;{' '}
                            <Ui>hrm.designations.import</Ui> দিয়ে স্প্রেডশিট
                            থেকে আনা যায়। HR অফিসাররা যোগ, সম্পাদনা ও ইমপোর্ট
                            করতে পারেন, কিন্তু মোছা সব কোম্পানিকে প্রভাবিত করে
                            বলে সেটি একজন অ্যাডমিনিস্ট্রেটরের কাজ।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="No company needs to be selected. Search the list before adding: someone in another company may already have added the title you need."
                    bn="কোনো কোম্পানি বেছে নেওয়ার দরকার নেই। যোগ করার আগে তালিকায় খুঁজে দেখুন: অন্য কোম্পানির কেউ হয়তো আপনার দরকারি পদবিটি আগেই যোগ করেছেন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open HRM → Designations."
                        bn="HRM → Designations খুলুন।"
                    >
                        <Shot
                            id="overview"
                            en="The designations every company shares."
                            bn="সব কোম্পানির শেয়ার করা পদবিগুলো।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New designation</Ui>, enter a code and
                                a name, and save with <Ui>Save &amp; close</Ui>{' '}
                                — or <Ui>Save &amp; add another</Ui> to keep
                                going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New designation</Ui> চাপুন, একটি কোড ও নাম
                                দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে সেভ
                                করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New designation: code, name and status."
                            bn="New designation: কোড, নাম ও অবস্থা।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted designation waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা পদবি Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>

                <SubHeading
                    en="Loading many designations from a spreadsheet"
                    bn="স্প্রেডশিট থেকে একসাথে অনেক পদবি আনা"
                />
                <Steps>
                    <Step
                        en={
                            <>
                                Press <Ui>Template</Ui> for an empty workbook
                                with the right headings, fill one row per
                                designation, then press <Ui>Import</Ui> and
                                upload it.
                            </>
                        }
                        bn={
                            <>
                                সঠিক শিরোনামসহ একটি খালি ওয়ার্কবুক পেতে{' '}
                                <Ui>Template</Ui> চাপুন, প্রতিটি পদবির জন্য একটি
                                সারি পূরণ করুন, তারপর <Ui>Import</Ui> চেপে
                                ফাইলটি আপলোড করুন।
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
                        en="Check which column feeds Code, Name and Status, and choose what happens to a row that matches a designation you already have: skip it or update it. A row matches when its code or its name is already used."
                        bn="কোন কলাম থেকে Code, Name ও Status আসবে তা মিলিয়ে নিন, আর আগে থেকে থাকা কোনো পদবির সাথে মিলে যাওয়া সারির কী হবে তা বেছে নিন: বাদ দেওয়া হবে, নাকি হালনাগাদ করা হবে। কোনো সারির কোড বা নাম আগে থেকেই ব্যবহৃত থাকলে সেটিকে মিল হিসেবে ধরা হয়।"
                    />
                    <Step
                        en="Review shows what Import would create, update, skip and reject before anything is written. Import writes the good rows; rejected rows are listed with the reason and can be downloaded, corrected and uploaded again."
                        bn="কিছু লেখার আগেই Review দেখায় Import কী তৈরি, হালনাগাদ, বাদ ও প্রত্যাখ্যান করবে। Import ঠিক সারিগুলো লেখে; প্রত্যাখ্যাত সারিগুলো কারণসহ তালিকায় দেখায়, সেগুলো ডাউনলোড করে ঠিক করে আবার আপলোড করা যায়।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as OP or SR-OP. Saved in capitals. No two designations may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন OP বা SR-OP। বড় হাতের অক্ষরে সেভ হয়। দুটি পদবির একই কোড হতে পারে না — মুছে ফেলা পদবিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique whatever the capitals, a deleted designation included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, অনন্য হতে হবে — মুছে ফেলা পদবিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the designation is in use:"
                        bn="পদবিটি ব্যবহারে আছে কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'In use, and offered for new records.',
                            bn: 'ব্যবহারে আছে, নতুন রেকর্ডের জন্য দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record but no longer offered.',
                            bn: 'রেকর্ডে রাখা আছে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: every designation is shared by every company."
                        bn="কোনো কোম্পানি ঘর নেই: প্রতিটি পদবি পুরো প্রতিষ্ঠানের।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "SR-OP" is already taken.'
                    en="Another designation uses the code, perhaps in small letters."
                    bn="অন্য একটি পদবি কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A designation named "Senior Operator" already exists.'
                    en="The name is taken, whatever the capitals — probably added from another company. Use the existing one."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, নামটি আগেই নেওয়া হয়েছে — সম্ভবত অন্য কোম্পানি থেকে যোগ করা। আগেরটিই ব্যবহার করুন।"
                />
                <Message
                    text='A deleted designation is named "Senior Operator". Restore it from the Deleted tab instead.'
                    en="A deleted designation still holds the name. Ask someone who can restore it rather than making a second."
                    bn="মুছে ফেলা একটি পদবির এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে যিনি ফিরিয়ে আনতে পারেন তাকে সেটি ফিরিয়ে আনতে বলুন।"
                />
                <Message
                    text='The code "OP" belongs to "Operator" but the name "Supervisor" belongs to the designation coded "SUP". Correct one of them.'
                    en="An import row points at two different designations. Fix the code or the name in the sheet and upload the row again."
                    bn="ইমপোর্টের একটি সারি দুটি ভিন্ন পদবিকে নির্দেশ করছে। শিটে কোড বা নাম ঠিক করে সারিটি আবার আপলোড করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Every company shares this list. Renaming, deactivating or deleting a designation changes it for all of them, not just the company you are working in."
                    bn="সব কোম্পানি এই তালিকা শেয়ার করে। কোনো পদবির নাম বদলানো, নিষ্ক্রিয় করা বা মোছা শুধু আপনার কোম্পানিতে নয়, সবগুলোতেই প্রভাব ফেলে।"
                />
                <Callout
                    tone="warn"
                    en="Choosing Update on an import overwrites the matched designations' codes and names with the sheet's. Use Skip when the sheet is only meant to add new ones."
                    bn="ইমপোর্টে Update বেছে নিলে মিলে যাওয়া পদবিগুলোর কোড ও নাম শিটের মান দিয়ে বদলে যায়। শিট দিয়ে শুধু নতুন পদবি যোগ করতে চাইলে Skip বেছে নিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="hrm.departments"
                    en="Departments, which each company keeps for itself."
                    bn="বিভাগ, যা প্রতিটি কোম্পানি নিজের জন্য রাখে।"
                />
            </GuideSection>
        </Guide>
    );
}
