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
 * Projects → Settings → Product Categories (`projects.settings.product-categories.index`), with its
 * New product category / Edit dialog and the Deleted tab. Source:
 * documentation/projects.md §4 (Product categories).
 */
export default function ProductCategoriesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The product categories of the company you are working in — the groups the company's products (Projects → Settings → Products) are sorted into. Each company keeps its own list, and every new company starts with 13 standard ones (Raw Materials, Consumables, Spare Parts, Services and the like) that you can rename, deactivate or delete."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার পণ্যের শ্রেণিগুলো — কোম্পানির পণ্যগুলো (Projects → Settings → Products) যেসব দলে ভাগ করা হয়। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে, এবং প্রতিটি নতুন কোম্পানি ১৩টি সাধারণ শ্রেণি (Raw Materials, Consumables, Spare Parts, Services ইত্যাদি) নিয়ে শুরু হয়, যেগুলোর নাম বদলানো, নিষ্ক্রিয় করা বা মোছা যায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.product-categories.view</Ui>{' '}
                            opens the list;{' '}
                            <Ui>projects.settings.product-categories.create</Ui>
                            ,{' '}
                            <Ui>projects.settings.product-categories.update</Ui>{' '}
                            and{' '}
                            <Ui>projects.settings.product-categories.delete</Ui>{' '}
                            add, edit and delete;{' '}
                            <Ui>
                                projects.settings.product-categories.restore
                            </Ui>{' '}
                            and{' '}
                            <Ui>
                                projects.settings.product-categories.force-delete
                            </Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            holds them all. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.product-categories.view</Ui>{' '}
                            থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>projects.settings.product-categories.create</Ui>
                            ,{' '}
                            <Ui>projects.settings.product-categories.update</Ui>{' '}
                            ও{' '}
                            <Ui>projects.settings.product-categories.delete</Ui>{' '}
                            দিয়ে যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>
                                projects.settings.product-categories.restore
                            </Ui>{' '}
                            ও{' '}
                            <Ui>
                                projects.settings.product-categories.force-delete
                            </Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোলের কাছে
                            এর সবগুলোই আছে। এর সাথে কাজ করার জন্য একটি কোম্পানিও
                            লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and every category you add, belongs to the company shown there. Look through the standard categories first — the one you need may already be there."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যে শ্রেণি যোগ করবেন সবই সেখানে দেখানো কোম্পানির। আগে সাধারণ শ্রেণিগুলো দেখে নিন — আপনার দরকারি শ্রেণিটি হয়তো আগেই আছে।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Product Categories. The description under the title names the company the list belongs to. Search by code or name in the row under the headings."
                        bn="Projects → Settings → Product Categories খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। শিরোনামের নিচের সারিতে কোড বা নাম দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The product categories of the selected company, starting with the standard set."
                            bn="বেছে নেওয়া কোম্পানির পণ্যের শ্রেণিগুলো, সাধারণ শ্রেণিগুলো দিয়ে শুরু।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New product category</Ui>, enter a
                                code and a name, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New product category</Ui> চাপুন, একটি কোড ও
                                নাম দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে
                                সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New product category: code, name, status and description."
                            bn="New product category: কোড, নাম, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="A standard category you do not use can be made Inactive, or deleted. To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="যে সাধারণ শ্রেণিটি আপনি ব্যবহার করেন না সেটিকে Inactive করা বা মোছা যায়। একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted category waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা শ্রেণি Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as RAW or RAW-01. Saved in capitals. No two categories of the company may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন RAW বা RAW-01। বড় হাতের অক্ষরে সেভ হয়। কোম্পানির দুটি শ্রেণির একই কোড হতে পারে না — মুছে ফেলা শ্রেণিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted category included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা শ্রেণিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes about what belongs in the category. Shown in the form, not in the list."
                        bn="শ্রেণিতে কী থাকবে সে সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the category is in use:"
                        bn="শ্রেণিটি ব্যবহারে আছে কি না:"
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
                        en="There is no company field: a category always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: শ্রেণি সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: categories beyond the standard set are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: সাধারণ শ্রেণিগুলোর বাইরে শ্রেণি একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "RAW" is already taken in this company.'
                    en="Another category of this company uses the code, perhaps in small letters. The same code in another company is fine."
                    bn="এই কোম্পানির অন্য একটি শ্রেণি কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে। অন্য কোম্পানিতে একই কোড থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A product category named "Raw Materials" already exists in this company.'
                    en="The name is taken in this company, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, এই কোম্পানিতে নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted product category in this company is named "Raw Materials". Restore it from the Deleted tab instead.'
                    en="A deleted category still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি শ্রেণির এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Used by 3 products. Move them to another category, or deactivate this one instead."
                    en="Products are in this category, deleted products included, so it cannot be deleted — neither moved to Deleted nor deleted for good. Edit those products onto another category, or make this one Inactive: its products keep it, and new products cannot choose it. Bulk delete deletes the free categories and names the kept ones."
                    bn="এই শ্রেণিতে পণ্য আছে, মুছে ফেলা পণ্যসহ, তাই এটি মোছা যায় না — Deleted-এ পাঠানোও না, চিরতরে মোছাও না। ওই পণ্যগুলো সম্পাদনা করে অন্য শ্রেণিতে নিন, অথবা এটিকে Inactive করুন: এর পণ্যগুলো শ্রেণিটি রেখে দেবে, নতুন পণ্য এটি বেছে নিতে পারবে না। একসাথে মোছার সময় খালি শ্রেণিগুলো মোছা হয় এবং যেগুলো রাখা হলো সেগুলোর নাম জানানো হয়।"
                />
                <Message
                    text="Select a company in the header before adding a product category."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding categories while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় শ্রেণি যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ করার আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="A company that still has product categories — deleted ones and the standard set included — cannot be deleted. Make the company Inactive instead, or delete its categories for good first."
                    bn="যে কোম্পানিতে এখনো পণ্যের শ্রেণি আছে — মুছে ফেলা ও সাধারণ শ্রেণিসহ — সেটি মোছা যায় না। তার বদলে কোম্পানিটিকে Inactive করুন, অথবা আগে এর শ্রেণিগুলো চিরতরে মুছুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.settings.products"
                    en="The products sorted into these categories."
                    bn="যেসব পণ্য এই শ্রেণিগুলোতে ভাগ করা হয়।"
                />
                <RelatedGuide
                    to="admin.companies"
                    en="The companies product categories belong to."
                    bn="যেসব কোম্পানির অধীনে পণ্যের শ্রেণিগুলো থাকে।"
                />
                <RelatedGuide
                    to="projects.settings.units"
                    en="The units quantities are counted in."
                    bn="যেসব এককে পরিমাণ গোনা হয়।"
                />
            </GuideSection>
        </Guide>
    );
}
