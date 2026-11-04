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
 * Projects → Settings → Suppliers (`projects.settings.suppliers.index`), with its New supplier /
 * Edit dialog and the Deleted tab. Source: documentation/projects.md §4
 * (Suppliers).
 */
export default function SuppliersGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The suppliers of the company you are working in — the firms you buy materials and services from and, later, pay bills to. It is the one supplier list for the whole application. Each company keeps its own list, so two companies may both have a supplier coded BM-01."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার সরবরাহকারীরা — যেসব প্রতিষ্ঠান থেকে আপনি মালামাল ও সেবা কেনেন এবং পরে বিল পরিশোধ করবেন। পুরো অ্যাপ্লিকেশনে সরবরাহকারীর তালিকা এই একটিই। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে, তাই দুটি কোম্পানিতেই BM-01 কোডের সরবরাহকারী থাকতে পারে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.suppliers.view</Ui> opens the
                            list; <Ui>projects.settings.suppliers.create</Ui>,{' '}
                            <Ui>projects.settings.suppliers.update</Ui> and{' '}
                            <Ui>projects.settings.suppliers.delete</Ui> add,
                            edit and delete;{' '}
                            <Ui>projects.settings.suppliers.restore</Ui> and{' '}
                            <Ui>projects.settings.suppliers.force-delete</Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            holds them all. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.suppliers.view</Ui> থাকলে
                            তালিকা খোলা যায়;{' '}
                            <Ui>projects.settings.suppliers.create</Ui>,{' '}
                            <Ui>projects.settings.suppliers.update</Ui> ও{' '}
                            <Ui>projects.settings.suppliers.delete</Ui> দিয়ে
                            যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.settings.suppliers.restore</Ui> ও{' '}
                            <Ui>projects.settings.suppliers.force-delete</Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোলের কাছে
                            এর সবগুলোই আছে। এর সাথে কাজ করার জন্য একটি কোম্পানিও
                            লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and every supplier you add, belongs to the company shown there. Every supplier needs a category, so make sure Projects → Settings → Supplier Categories has the one you need, and that it is Active."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যে সরবরাহকারী যোগ করবেন সবই সেখানে দেখানো কোম্পানির। প্রতিটি সরবরাহকারীর একটি শ্রেণি লাগে, তাই Projects → Settings → Supplier Categories-এ আপনার দরকারি শ্রেণিটি আছে এবং সেটি Active কি না দেখে নিন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Suppliers. The description under the title names the company the list belongs to. Search by code, name, category, contact person, phone, email, TIN or BIN in the row under the headings."
                        bn="Projects → Settings → Suppliers খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। শিরোনামের নিচের সারিতে কোড, নাম, শ্রেণি, যোগাযোগকারী, ফোন, ইমেইল, TIN বা BIN দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The suppliers of the selected company, with their categories and tax numbers."
                            bn="বেছে নেওয়া কোম্পানির সরবরাহকারীরা, তাদের শ্রেণি ও কর নম্বরসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New supplier</Ui>, enter at least a
                                code, a name and a category, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                                The tax numbers and the bank account can be
                                added later.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New supplier</Ui> চাপুন, অন্তত একটি কোড, নাম
                                ও শ্রেণি দিন, তারপর <Ui>Save &amp; close</Ui>{' '}
                                দিয়ে সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>। কর নম্বর ও
                                ব্যাংক হিসাব পরে যোগ করা যায়।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New supplier: the supplier, its tax and licence numbers, and its bank account."
                            bn="New supplier: সরবরাহকারী, তার কর ও লাইসেন্স নম্বর এবং ব্যাংক হিসাব।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted supplier waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা সরবরাহকারী Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as BUILDMART or BM-01. Saved in capitals. No two suppliers of the company may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন BUILDMART বা BM-01। বড় হাতের অক্ষরে সেভ হয়। কোম্পানির দুটি সরবরাহকারীর একই কোড হতে পারে না — মুছে ফেলা সরবরাহকারীও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 150 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted supplier included."
                        bn="সর্বোচ্চ ১৫০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা সরবরাহকারীও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Category"
                        en="Required. Only Active categories are offered; a supplier whose category was made Inactive later keeps it."
                        bn="আবশ্যক। শুধু Active শ্রেণি দেখানো হয়; কোনো সরবরাহকারীর শ্রেণি পরে Inactive করা হলে সে শ্রেণিটি রেখে দেয়।"
                    />
                    <Field
                        label="Contact person, Phone, Email, Address"
                        en="Optional, as on a client: the phone takes digits, spaces and + ( ) -; the email is saved in small letters and two suppliers may share one."
                        bn="ঐচ্ছিক, ক্লায়েন্টের মতোই: ফোনে সংখ্যা, ফাঁকা জায়গা ও + ( ) - চলে; ইমেইল ছোট হাতের অক্ষরে সেভ হয় এবং দুটি সরবরাহকারীর একই ইমেইল থাকতে পারে।"
                    />
                    <Field
                        label="TIN"
                        en="Optional, exactly 12 digits. Not unique: branches of one firm may share it."
                        bn="ঐচ্ছিক, ঠিক ১২টি সংখ্যা। অনন্য নয়: একই প্রতিষ্ঠানের শাখাগুলোর একই TIN থাকতে পারে।"
                    />
                    <Field
                        label="BIN (VAT registration)"
                        en="Optional, up to 20 digits, with or without hyphens. Not unique."
                        bn="ঐচ্ছিক, সর্বোচ্চ ২০টি সংখ্যা, হাইফেনসহ বা ছাড়া। অনন্য নয়।"
                    />
                    <Field
                        label="Trade licence no."
                        en="Optional, up to 50 characters, typed as printed on the licence."
                        bn="ঐচ্ছিক, সর্বোচ্চ ৫০ অক্ষর, লাইসেন্সে যেমন ছাপা আছে তেমন।"
                    />
                    <Field
                        label="Bank, Branch, Account name, Account number, Routing number"
                        en="Optional — the one account you pay the supplier into. The account number takes letters, digits, spaces and hyphens and is saved in capitals; the routing number is digits only. Shown in the form, not in the list."
                        bn="ঐচ্ছিক — যে একটি হিসাবে আপনি সরবরাহকারীকে টাকা দেন। হিসাব নম্বরে অক্ষর, সংখ্যা, ফাঁকা জায়গা ও হাইফেন চলে এবং বড় হাতের অক্ষরে সেভ হয়; রাউটিং নম্বরে শুধু সংখ্যা। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes about the supplier. Shown in the form, not in the list."
                        bn="সরবরাহকারী সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the supplier is in use:"
                        bn="সরবরাহকারীটি ব্যবহারে আছে কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'In use, and offered for new purchases.',
                            bn: 'ব্যবহারে আছে, নতুন কেনাকাটার জন্য দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record but no longer offered.',
                            bn: 'রেকর্ডে রাখা আছে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: a supplier always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: সরবরাহকারী সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: suppliers are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: সরবরাহকারী একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "BM-01" is already taken in this company.'
                    en="Another supplier of this company uses the code, perhaps in small letters. The same code in another company is fine."
                    bn="এই কোম্পানির অন্য একটি সরবরাহকারী কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে। অন্য কোম্পানিতে একই কোড থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A deleted supplier in this company is named "Buildmart Supplies Ltd.". Restore it from the Deleted tab instead.'
                    en="A deleted supplier still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি সরবরাহকারীর এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Choose an active supplier category."
                    en="No category was chosen, or the chosen one has been made Inactive or deleted meanwhile."
                    bn="কোনো শ্রেণি বেছে নেওয়া হয়নি, অথবা বেছে নেওয়া শ্রেণিটি এর মধ্যে Inactive করা বা মুছে ফেলা হয়েছে।"
                />
                <Message
                    text="A TIN is 12 digits."
                    en="The TIN is too short, too long, or holds something other than digits."
                    bn="TIN খুব ছোট, খুব বড়, অথবা তাতে সংখ্যা ছাড়া অন্য কিছু আছে।"
                />
                <Message
                    text="Select a company in the header before adding a supplier."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding suppliers while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় সরবরাহকারী যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ করার আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="Check the bank account number twice: it is not verified against the bank, and a wrong digit sends a payment to someone else."
                    bn="ব্যাংক হিসাব নম্বর দুবার মিলিয়ে নিন: এটি ব্যাংকের সাথে যাচাই করা হয় না, এবং একটি ভুল সংখ্যায় টাকা অন্য কারও কাছে চলে যায়।"
                />
                <Callout
                    tone="warn"
                    en="A company that still has suppliers — deleted ones included — cannot be deleted. Make the company Inactive instead, or delete its suppliers for good first."
                    bn="যে কোম্পানিতে এখনো সরবরাহকারী আছে — মুছে ফেলাগুলোসহ — সেটি মোছা যায় না। তার বদলে কোম্পানিটিকে Inactive করুন, অথবা আগে তার সরবরাহকারীদের চিরতরে মুছুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.settings.supplier-categories"
                    en="The categories every supplier is placed in."
                    bn="যে শ্রেণিগুলোতে প্রতিটি সরবরাহকারীকে রাখা হয়।"
                />
                <RelatedGuide
                    to="projects.settings.clients"
                    en="The other side: the clients projects are run for."
                    bn="অন্য দিক: যেসব ক্লায়েন্টের জন্য প্রকল্প চালানো হয়।"
                />
                <RelatedGuide
                    to="admin.companies"
                    en="The companies suppliers belong to."
                    bn="যেসব কোম্পানির অধীনে সরবরাহকারীরা থাকে।"
                />
            </GuideSection>
        </Guide>
    );
}
