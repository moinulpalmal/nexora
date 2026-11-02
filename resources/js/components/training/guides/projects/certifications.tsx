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
 * Projects → Certifications (`projects.certifications.index`), with its New
 * certification / Edit dialog and the Deleted tab. Source:
 * documentation/projects.md §4 (Certifications).
 */
export default function CertificationsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The catalogue of standards a supplier may hold or a tender may demand — ISO 9001, ISO 14001, amfori BSCI, OEKO-TEX, CE, the BSTI mark and the like. Each row is a standard, not a certificate someone holds: certificate numbers and expiry dates will be kept where the certificate is used. There is one list for the whole group, and a new installation already has the common standards."
                    bn="সরবরাহকারীর থাকতে পারে বা দরপত্রে চাওয়া হতে পারে এমন মানদণ্ডের তালিকা — ISO 9001, ISO 14001, amfori BSCI, OEKO-TEX, CE, BSTI মার্ক ইত্যাদি। প্রতিটি সারি একটি মানদণ্ড, কারও কাছে থাকা সনদ নয়: সনদের নম্বর ও মেয়াদ যেখানে সনদ ব্যবহার হয় সেখানে রাখা হবে। পুরো গ্রুপের জন্য তালিকা একটিই, এবং নতুন ইনস্টলেশনে প্রচলিত মানদণ্ডগুলো আগে থেকেই থাকে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.certifications.view</Ui> opens the
                            list; <Ui>projects.certifications.create</Ui>,{' '}
                            <Ui>projects.certifications.update</Ui> and{' '}
                            <Ui>projects.certifications.delete</Ui> add, edit
                            and delete; <Ui>projects.certifications.restore</Ui>{' '}
                            and <Ui>projects.certifications.force-delete</Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            may view, add and edit, but not delete or restore:
                            the list is shared, so removing a certification is
                            left to the super admin. No company needs to be
                            selected.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.certifications.view</Ui> থাকলে তালিকা
                            খোলা যায়; <Ui>projects.certifications.create</Ui>,{' '}
                            <Ui>projects.certifications.update</Ui> ও{' '}
                            <Ui>projects.certifications.delete</Ui> দিয়ে যোগ,
                            সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.certifications.restore</Ui> ও{' '}
                            <Ui>projects.certifications.force-delete</Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোল দেখতে,
                            যোগ করতে ও সম্পাদনা করতে পারে, কিন্তু মুছতে বা
                            ফিরিয়ে আনতে পারে না: তালিকাটি সবার, তাই
                            সার্টিফিকেশন মোছার কাজ সুপার অ্যাডমিনের। কোনো
                            কোম্পানি বেছে নেওয়ার দরকার নেই।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Search the list first — the standard you need is probably there already, perhaps in another edition. Every certification needs a category, so check Certification Categories has the right one."
                    bn="আগে তালিকায় খুঁজে দেখুন — যে মানদণ্ড দরকার সেটি সম্ভবত আগে থেকেই আছে, হয়তো অন্য সংস্করণে। প্রতিটি সার্টিফিকেশনের একটি শ্রেণি লাগে, তাই Certification Categories-এ সঠিক শ্রেণিটি আছে কি না দেখে নিন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Certifications. Search by code, name, category, issuing body or version in the row under the headings."
                        bn="Projects → Certifications খুলুন। শিরোনামের নিচের সারিতে কোড, নাম, শ্রেণি, প্রদানকারী সংস্থা বা সংস্করণ দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The standard certifications every company shares."
                            bn="সব কোম্পানির জন্য একই মানক সার্টিফিকেশন।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New certification</Ui>, enter a code,
                                a name and a category — and the issuing body and
                                version when there is one — then save with{' '}
                                <Ui>Save &amp; close</Ui> or{' '}
                                <Ui>Save &amp; add another</Ui>.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New certification</Ui> চাপুন, একটি কোড, নাম
                                ও শ্রেণি দিন — আর থাকলে প্রদানকারী সংস্থা ও
                                সংস্করণ — তারপর <Ui>Save &amp; close</Ui> বা{' '}
                                <Ui>Save &amp; add another</Ui> দিয়ে সেভ করুন।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New certification: code, name, category, issuing body, version, status and description."
                            bn="New certification: কোড, নাম, শ্রেণি, প্রদানকারী সংস্থা, সংস্করণ, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="When a standard gets a new edition, add it as a new row with the same name and the new version, and make the old edition Inactive once it is no longer accepted."
                        bn="কোনো মানদণ্ডের নতুন সংস্করণ এলে একই নাম ও নতুন সংস্করণ দিয়ে নতুন সারি যোগ করুন, আর পুরোনো সংস্করণ আর গ্রহণযোগ্য না থাকলে সেটি Inactive করুন।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 30 letters and digits, optionally joined by hyphens or underscores, such as ISO9001-2015 or BSCI. Saved in capitals. No two certifications may share a code — a deleted one included."
                        bn="সর্বোচ্চ ৩০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন ISO9001-2015 বা BSCI। বড় হাতের অক্ষরে সেভ হয়। দুটি সার্টিফিকেশনের একই কোড হতে পারে না — মুছে ফেলাটিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 150 characters; extra spaces are removed. The same name may be used once per version — ISO 9001 2008 and ISO 9001 2015 can both exist — whatever the capitals, a deleted certification included."
                        bn="সর্বোচ্চ ১৫০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। একই নাম প্রতি সংস্করণে একবার ব্যবহার করা যায় — ISO 9001 2008 ও ISO 9001 2015 দুটোই থাকতে পারে — বড়-ছোট অক্ষর যা-ই হোক, মুছে ফেলাটিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Category"
                        en="Required. Only active categories are offered; a certification already in a category that was later made inactive keeps it."
                        bn="আবশ্যক। শুধু সক্রিয় শ্রেণিগুলো দেখানো হয়; পরে নিষ্ক্রিয় হওয়া শ্রেণিতে আগে থেকে থাকা সার্টিফিকেশন সেটি রেখে দেয়।"
                    />
                    <Field
                        label="Issuing body"
                        en="Optional. Who publishes the standard — ISO, amfori, OEKO-TEX Association, BSTI…"
                        bn="ঐচ্ছিক। কে মানদণ্ডটি প্রকাশ করে — ISO, amfori, OEKO-TEX Association, BSTI…"
                    />
                    <Field
                        label="Version / edition"
                        en="Optional, up to 20 characters, such as 2015 or 2014/68/EU. Leave it blank for a mark with no edition, such as CE — but only one row of a name may be left blank."
                        bn="ঐচ্ছিক, সর্বোচ্চ ২০ অক্ষর, যেমন 2015 বা 2014/68/EU। CE-এর মতো সংস্করণহীন মার্কের জন্য ফাঁকা রাখুন — তবে একটি নামের শুধু একটি সারিই ফাঁকা রাখা যায়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes on the standard's scope. Shown in the form, not in the list."
                        bn="মানদণ্ডের পরিধি সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the certification is offered:"
                        bn="সার্টিফিকেশনটি দেখানো হয় কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Offered wherever a certification is chosen.',
                            bn: 'যেখানে সার্টিফিকেশন বেছে নেওয়া হয় সেখানে দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record but no longer offered.',
                            bn: 'রেকর্ডে রাখা আছে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: every company shares the same certifications."
                        bn="কোনো কোম্পানি ঘর নেই: সব কোম্পানি একই সার্টিফিকেশন ব্যবহার করে।"
                    />
                    <Bullet
                        en="There is no spreadsheet import. The common standards come with the installation; others are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই। প্রচলিত মানদণ্ডগুলো ইনস্টলেশনের সাথেই আসে; অন্যগুলো একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "ISO9001-2015" is already taken.'
                    en="Another certification uses the code, perhaps in small letters."
                    bn="অন্য একটি সার্টিফিকেশন কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A certification named "ISO 9001 Quality Management Systems" already exists in this version.'
                    en="The name is taken in the same version (or both are blank), whatever the capitals. Give the new edition its version."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, একই সংস্করণে (বা দুটোই ফাঁকা) নামটি আগেই নেওয়া হয়েছে। নতুন সংস্করণটিকে তার সংস্করণ দিন।"
                />
                <Message
                    text='A deleted certification is named "ISO 9001 Quality Management Systems" in this version. Restore it from the Deleted tab instead.'
                    en="A deleted certification still holds the name in that version. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি সার্টিফিকেশনের ওই সংস্করণে এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Choose an active certification category."
                    en="The category picked is inactive or deleted. Choose another, or make that category Active first."
                    bn="বেছে নেওয়া শ্রেণিটি নিষ্ক্রিয় বা মুছে ফেলা। অন্যটি বেছে নিন, অথবা আগে শ্রেণিটি Active করুন।"
                />
                <Message
                    text='Use letters and digits, optionally joined by single hyphens or underscores, e.g. "ISO9001-2015".'
                    en="The code holds a space or a symbol. Use letters and digits only, joined by single hyphens or underscores."
                    bn="কোডে ফাঁকা জায়গা বা কোনো চিহ্ন আছে। শুধু অক্ষর ও সংখ্যা ব্যবহার করুন, একক হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="Editing a certification to change its version rewrites that row for every company. To record a new edition, add a new row instead and keep the old one."
                    bn="কোনো সার্টিফিকেশনের সংস্করণ বদলাতে সম্পাদনা করলে সব কোম্পানির জন্য সেই সারিটিই বদলে যায়। নতুন সংস্করণ রাখতে বরং নতুন সারি যোগ করুন এবং পুরোনোটি রেখে দিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.certification-categories"
                    en="The categories certifications are filed under."
                    bn="যে শ্রেণিগুলোতে সার্টিফিকেশন রাখা হয়।"
                />
            </GuideSection>
        </Guide>
    );
}
