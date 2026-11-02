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
 * Projects → Certification Categories (`projects.certification-categories.index`),
 * with its New certification category / Edit dialog and the Deleted tab.
 * Source: documentation/projects.md §4 (Certification categories).
 */
export default function CertificationCategoriesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The groups certifications are filed under — quality management, environmental, social compliance, steel & metals, pipes & plastics and the like. Every certification is in exactly one category. There is one list for the whole group: every company sees the same categories, so an edit or a delete shows in all of them. A new installation already has 16 standard categories."
                    bn="সার্টিফিকেশনগুলো যে দলে রাখা হয় — কোয়ালিটি ম্যানেজমেন্ট, পরিবেশ, সোশ্যাল কমপ্লায়েন্স, স্টিল ও ধাতু, পাইপ ও প্লাস্টিক ইত্যাদি। প্রতিটি সার্টিফিকেশন ঠিক একটি শ্রেণিতে থাকে। পুরো গ্রুপের জন্য তালিকা একটিই: প্রতিটি কোম্পানি একই শ্রেণি দেখে, তাই কোনো সম্পাদনা বা মোছা সবগুলো কোম্পানিতেই দেখা যায়। নতুন ইনস্টলেশনে আগে থেকেই ১৬টি মানক শ্রেণি থাকে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.certification-categories.view</Ui>{' '}
                            opens the list;{' '}
                            <Ui>projects.certification-categories.create</Ui>,{' '}
                            <Ui>projects.certification-categories.update</Ui>{' '}
                            and{' '}
                            <Ui>projects.certification-categories.delete</Ui>{' '}
                            add, edit and delete;{' '}
                            <Ui>projects.certification-categories.restore</Ui>{' '}
                            and{' '}
                            <Ui>
                                projects.certification-categories.force-delete
                            </Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            may view, add and edit, but not delete or restore:
                            the list is shared, so removing a category is left
                            to the super admin. No company needs to be selected.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.certification-categories.view</Ui>{' '}
                            থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>projects.certification-categories.create</Ui>,{' '}
                            <Ui>projects.certification-categories.update</Ui> ও{' '}
                            <Ui>projects.certification-categories.delete</Ui>{' '}
                            দিয়ে যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.certification-categories.restore</Ui> ও{' '}
                            <Ui>
                                projects.certification-categories.force-delete
                            </Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোল দেখতে,
                            যোগ করতে ও সম্পাদনা করতে পারে, কিন্তু মুছতে বা
                            ফিরিয়ে আনতে পারে না: তালিকাটি সবার, তাই শ্রেণি
                            মোছার কাজ সুপার অ্যাডমিনের। কোনো কোম্পানি বেছে
                            নেওয়ার দরকার নেই।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Look through the standard categories first — the one you need is probably there already. Agree any new category with the other companies: the list is shared, so a category made here is offered for every certification."
                    bn="আগে মানক শ্রেণিগুলো দেখে নিন — যেটি দরকার সেটি সম্ভবত আগে থেকেই আছে। নতুন কোনো শ্রেণি অন্য কোম্পানিগুলোর সাথে ঠিক করে নিন: তালিকাটি সবার, তাই এখানে তৈরি করা শ্রেণি প্রতিটি সার্টিফিকেশনের জন্য দেখানো হয়।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Certification Categories. Search by code or name in the row under the headings."
                        bn="Projects → Certification Categories খুলুন। শিরোনামের নিচের সারিতে কোড বা নাম দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The certification categories every company shares."
                            bn="সব কোম্পানির জন্য একই সার্টিফিকেশনের শ্রেণি।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New certification category</Ui>, enter
                                a code and a name, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New certification category</Ui> চাপুন, একটি
                                কোড ও নাম দিন, তারপর <Ui>Save &amp; close</Ui>{' '}
                                দিয়ে সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New certification category: code, name, status and description."
                            bn="New certification category: কোড, নাম, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="To retire a category that certifications still use, edit it and make it Inactive: its certifications keep it, and it is no longer offered for new ones."
                        bn="যে শ্রেণি সার্টিফিকেশনগুলো এখনো ব্যবহার করছে সেটি বাদ দিতে সম্পাদনা করে Inactive করুন: তার সার্টিফিকেশনগুলো শ্রেণিটি রেখে দেয়, কিন্তু নতুনগুলোর জন্য আর দেখানো হয় না।"
                    />
                    <Step
                        en="To delete several at once, tick their rows and press Delete in the bar that appears. Categories still in use are left alone, and the message names them."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিয়ে যে বারটি দেখা যায় তাতে Delete চাপুন। যেগুলো এখনো ব্যবহারে আছে সেগুলো মোছা হয় না, এবং বার্তায় সেগুলোর নাম থাকে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as QMS or SOC. Saved in capitals. No two categories may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন QMS বা SOC। বড় হাতের অক্ষরে সেভ হয়। দুটি শ্রেণির একই কোড হতে পারে না — মুছে ফেলা শ্রেণিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique whatever the capitals, a deleted category included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, অনন্য হতে হবে — মুছে ফেলা শ্রেণিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes on what belongs in the category. Shown in the form, not in the list."
                        bn="শ্রেণিতে কী পড়ে সে সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the category is offered:"
                        bn="শ্রেণিটি দেখানো হয় কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Offered when a certification is added or edited.',
                            bn: 'সার্টিফিকেশন যোগ বা সম্পাদনার সময় দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept by the certifications already in it, but no longer offered.',
                            bn: 'যে সার্টিফিকেশনগুলো আগে থেকে আছে সেগুলো রেখে দেয়, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: every company shares the same categories."
                        bn="কোনো কোম্পানি ঘর নেই: সব কোম্পানি একই শ্রেণি ব্যবহার করে।"
                    />
                    <Bullet
                        en="There is no spreadsheet import. The 16 standard categories come with the installation; others are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই। ১৬টি মানক শ্রেণি ইনস্টলেশনের সাথেই আসে; অন্যগুলো একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "QMS" is already taken.'
                    en="Another category uses the code, perhaps in small letters."
                    bn="অন্য একটি শ্রেণি কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A certification category named "Quality Management" already exists.'
                    en="The name is taken, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted certification category is named "Quality Management". Restore it from the Deleted tab instead.'
                    en="A deleted category still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি শ্রেণির এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Used by 3 certifications. Move them to another category, or deactivate this one instead."
                    en="Certifications — deleted ones included — are still in the category, so it cannot be deleted. Move them to another category, or make this one Inactive."
                    bn="সার্টিফিকেশনগুলো — মুছে ফেলাগুলোসহ — এখনো এই শ্রেণিতে আছে, তাই এটি মোছা যায় না। সেগুলোকে অন্য শ্রেণিতে সরান, অথবা এটিকে Inactive করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="Renaming, deactivating or deleting a category changes it for every company, not only yours. If one company stops using a category, leave it Active for the others."
                    bn="শ্রেণির নাম বদলানো, নিষ্ক্রিয় করা বা মোছা শুধু আপনার নয়, সব কোম্পানির জন্যই তা বদলে দেয়। কোনো একটি কোম্পানি একটি শ্রেণি ব্যবহার বন্ধ করলে অন্যদের জন্য সেটি Active রেখে দিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.certifications"
                    en="The certifications each category classifies."
                    bn="যে সার্টিফিকেশনগুলো প্রতিটি শ্রেণি ভাগ করে।"
                />
            </GuideSection>
        </Guide>
    );
}
