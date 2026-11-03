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
 * Projects → Products (`projects.products.index`), with its New product / Edit
 * dialog and the Deleted tab. Source: documentation/projects.md §4 (Products).
 */
export default function ProductsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The products of the company you are working in — what it buys, stocks or supplies, each in one of the company's product categories. Each company keeps its own list."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার পণ্যগুলো — কোম্পানি যা কেনে, মজুত রাখে বা সরবরাহ করে, প্রতিটি কোম্পানির কোনো একটি পণ্যের শ্রেণিতে। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.products.view</Ui> opens the list;{' '}
                            <Ui>projects.products.create</Ui>,{' '}
                            <Ui>projects.products.update</Ui> and{' '}
                            <Ui>projects.products.delete</Ui> add, edit and
                            delete; <Ui>projects.products.restore</Ui> and{' '}
                            <Ui>projects.products.force-delete</Ui> work on the
                            Deleted tab. The project-manager role holds them
                            all. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.products.view</Ui> থাকলে তালিকা খোলা
                            যায়; <Ui>projects.products.create</Ui>,{' '}
                            <Ui>projects.products.update</Ui> ও{' '}
                            <Ui>projects.products.delete</Ui> দিয়ে যোগ,
                            সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>projects.products.restore</Ui> ও{' '}
                            <Ui>projects.products.force-delete</Ui> Deleted
                            ট্যাবে কাজ করে। project-manager রোলের কাছে এর
                            সবগুলোই আছে। এর সাথে কাজ করার জন্য একটি কোম্পানিও
                            লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and every product you add, belongs to the company shown there. The category you want must exist and be Active on Projects → Product Categories."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যে পণ্য যোগ করবেন সবই সেখানে দেখানো কোম্পানির। যে শ্রেণিটি চান সেটি Projects → Product Categories-এ থাকতে হবে এবং Active হতে হবে।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Products. The description under the title names the company the list belongs to. Search by code or name, or pick a category or status, in the row under the headings."
                        bn="Projects → Products খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। শিরোনামের নিচের সারিতে কোড বা নাম দিয়ে খুঁজুন, অথবা একটি শ্রেণি বা অবস্থা বেছে নিন।"
                    >
                        <Shot
                            id="overview"
                            en="The products of the selected company, with their categories."
                            bn="বেছে নেওয়া কোম্পানির পণ্যগুলো, তাদের শ্রেণিসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New product</Ui>, enter a code and a
                                name, choose the category, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New product</Ui> চাপুন, একটি কোড ও নাম দিন,
                                শ্রেণি বেছে নিন, তারপর <Ui>Save &amp; close</Ui>{' '}
                                দিয়ে সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New product: code, name, category, status and description."
                            bn="New product: কোড, নাম, শ্রেণি, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="A product you no longer use can be made Inactive, or deleted. To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="যে পণ্যটি আর ব্যবহার করেন না সেটিকে Inactive করা বা মোছা যায়। একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted product waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা পণ্য Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as CEM or CEM-50KG. Saved in capitals. No two products of the company may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন CEM বা CEM-50KG। বড় হাতের অক্ষরে সেভ হয়। কোম্পানির দুটি পণ্যের একই কোড হতে পারে না — মুছে ফেলা পণ্যও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 150 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted product included."
                        bn="সর্বোচ্চ ১৫০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা পণ্যও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Category"
                        en="Required — one of the company's Active product categories. A product whose category was made Inactive later keeps it when you edit it."
                        bn="আবশ্যক — কোম্পানির Active পণ্যের শ্রেণিগুলোর একটি। পরে শ্রেণিটি Inactive করা হলেও সম্পাদনার সময় পণ্যটি সেটি রেখে দেয়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes about the product. Shown in the form, not in the list."
                        bn="পণ্য সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the product is in use:"
                        bn="পণ্যটি ব্যবহারে আছে কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'In use, and offered for new entries.',
                            bn: 'ব্যবহারে আছে, নতুন এন্ট্রির জন্য দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record but no longer offered.',
                            bn: 'রেকর্ডে রাখা আছে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: a product always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: পণ্য সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: products are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: পণ্য একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="The category field is required."
                    en="Choose a category before saving."
                    bn="সেভ করার আগে একটি শ্রেণি বেছে নিন।"
                />
                <Message
                    text="Choose an active product category."
                    en="The category chosen was made Inactive or deleted meanwhile. Pick another, or reactivate it on Projects → Product Categories."
                    bn="বেছে নেওয়া শ্রেণিটি এর মধ্যে Inactive করা বা মোছা হয়েছে। অন্য একটি বেছে নিন, অথবা Projects → Product Categories-এ সেটি আবার সক্রিয় করুন।"
                />
                <Message
                    text='The code "CEM-50KG" is already taken in this company.'
                    en="Another product of this company uses the code, perhaps in small letters. The same code in another company is fine."
                    bn="এই কোম্পানির অন্য একটি পণ্য কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে। অন্য কোম্পানিতে একই কোড থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A product named "Portland Cement 50 kg" already exists in this company.'
                    en="The name is taken in this company, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, এই কোম্পানিতে নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted product in this company is named "Portland Cement 50 kg". Restore it from the Deleted tab instead.'
                    en="A deleted product still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি পণ্যের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Select a company in the header before adding a product."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding products while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় পণ্য যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ করার আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="A product, even a deleted one, keeps its category from being deleted, and a company that still has products cannot be deleted. Delete products for good first, or make the category or company Inactive instead."
                    bn="একটি পণ্য, মুছে ফেলা হলেও, তার শ্রেণিকে মুছতে দেয় না, এবং যে কোম্পানিতে এখনো পণ্য আছে সেটি মোছা যায় না। আগে পণ্যগুলো চিরতরে মুছুন, অথবা তার বদলে শ্রেণি বা কোম্পানিকে Inactive করুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.product-categories"
                    en="The categories products are sorted into."
                    bn="যেসব শ্রেণিতে পণ্যগুলো ভাগ করা হয়।"
                />
                <RelatedGuide
                    to="admin.companies"
                    en="The companies products belong to."
                    bn="যেসব কোম্পানির অধীনে পণ্যগুলো থাকে।"
                />
            </GuideSection>
        </Guide>
    );
}
