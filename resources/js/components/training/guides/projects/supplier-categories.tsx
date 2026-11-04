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
 * Projects → Settings → Supplier Categories (`projects.settings.supplier-categories.index`), with
 * its New supplier category / Edit dialog and the Deleted tab. Source:
 * documentation/projects.md §4 (Supplier categories).
 */
export default function SupplierCategoriesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The kinds of supplier you buy from — materials, services, subcontractors, equipment hire and the like. Every supplier is in exactly one category. There is one list for the whole group: every company sees the same categories, so an edit or a delete shows in all of them."
                    bn="আপনি যে ধরনের সরবরাহকারীর কাছ থেকে কেনেন — মালামাল, সেবা, সাব-কন্ট্রাক্টর, যন্ত্রপাতি ভাড়া ইত্যাদি। প্রতিটি সরবরাহকারী ঠিক একটি শ্রেণিতে থাকে। পুরো গ্রুপের জন্য তালিকা একটিই: প্রতিটি কোম্পানি একই শ্রেণি দেখে, তাই কোনো সম্পাদনা বা মোছা সবগুলো কোম্পানিতেই দেখা যায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.supplier-categories.view</Ui>{' '}
                            opens the list;{' '}
                            <Ui>
                                projects.settings.supplier-categories.create
                            </Ui>
                            ,{' '}
                            <Ui>
                                projects.settings.supplier-categories.update
                            </Ui>{' '}
                            and{' '}
                            <Ui>
                                projects.settings.supplier-categories.delete
                            </Ui>{' '}
                            add, edit and delete;{' '}
                            <Ui>
                                projects.settings.supplier-categories.restore
                            </Ui>{' '}
                            and{' '}
                            <Ui>
                                projects.settings.supplier-categories.force-delete
                            </Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            holds them all. No company needs to be selected.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.supplier-categories.view</Ui>{' '}
                            থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>
                                projects.settings.supplier-categories.create
                            </Ui>
                            ,{' '}
                            <Ui>
                                projects.settings.supplier-categories.update
                            </Ui>{' '}
                            ও{' '}
                            <Ui>
                                projects.settings.supplier-categories.delete
                            </Ui>{' '}
                            দিয়ে যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>
                                projects.settings.supplier-categories.restore
                            </Ui>{' '}
                            ও{' '}
                            <Ui>
                                projects.settings.supplier-categories.force-delete
                            </Ui>{' '}
                            Deleted ট্যাবে কাজ করে। project-manager রোলের কাছে
                            এর সবগুলোই আছে। কোনো কোম্পানি বেছে নেওয়ার দরকার
                            নেই।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Agree the categories with the other companies first: the list is shared, so a category made here is offered to every company's suppliers. Create the categories before the suppliers — a supplier cannot be saved without one."
                    bn="আগে অন্য কোম্পানিগুলোর সাথে শ্রেণিগুলো ঠিক করে নিন: তালিকাটি সবার, তাই এখানে তৈরি করা শ্রেণি প্রতিটি কোম্পানির সরবরাহকারীদের জন্য দেখানো হয়। সরবরাহকারীর আগে শ্রেণিগুলো তৈরি করুন — শ্রেণি ছাড়া সরবরাহকারী সেভ করা যায় না।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Supplier Categories. Search by code or name in the row under the headings."
                        bn="Projects → Settings → Supplier Categories খুলুন। শিরোনামের নিচের সারিতে কোড বা নাম দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The supplier categories every company shares."
                            bn="সব কোম্পানির জন্য একই সরবরাহকারীর শ্রেণি।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New supplier category</Ui>, enter a
                                code and a name, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New supplier category</Ui> চাপুন, একটি কোড ও
                                নাম দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে
                                সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New supplier category: code, name, status and description."
                            bn="New supplier category: কোড, নাম, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="To retire a category that suppliers still use, edit it and make it Inactive: its suppliers keep it, and it is no longer offered for new ones."
                        bn="যে শ্রেণি সরবরাহকারীরা এখনো ব্যবহার করছে সেটি বাদ দিতে সম্পাদনা করে Inactive করুন: তার সরবরাহকারীরা শ্রেণিটি রেখে দেয়, কিন্তু নতুনদের জন্য আর দেখানো হয় না।"
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
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as MAT or SUB-01. Saved in capitals. No two categories may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন MAT বা SUB-01। বড় হাতের অক্ষরে সেভ হয়। দুটি শ্রেণির একই কোড হতে পারে না — মুছে ফেলা শ্রেণিও এর মধ্যে ধরা হয়।"
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
                            en: 'Offered when a supplier is added or edited.',
                            bn: 'সরবরাহকারী যোগ বা সম্পাদনার সময় দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept by the suppliers already in it, but no longer offered.',
                            bn: 'যে সরবরাহকারীরা আগে থেকে আছে তারা রেখে দেয়, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: every company shares the same categories."
                        bn="কোনো কোম্পানি ঘর নেই: সব কোম্পানি একই শ্রেণি ব্যবহার করে।"
                    />
                    <Bullet
                        en="There is no spreadsheet import, and a new installation starts with an empty list: categories are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই, এবং নতুন ইনস্টলেশনে তালিকা খালি থাকে: শ্রেণি একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "MAT" is already taken.'
                    en="Another category uses the code, perhaps in small letters."
                    bn="অন্য একটি শ্রেণি কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A supplier category named "Materials" already exists.'
                    en="The name is taken, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted supplier category is named "Materials". Restore it from the Deleted tab instead.'
                    en="A deleted category still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি শ্রেণির এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Used by 3 suppliers. Move them to another category, or deactivate this one instead."
                    en="Suppliers in some company — deleted ones included — are still in the category, so it cannot be deleted. Move them to another category, or make this one Inactive."
                    bn="কোনো কোম্পানির সরবরাহকারীরা — মুছে ফেলাগুলোসহ — এখনো এই শ্রেণিতে আছে, তাই এটি মোছা যায় না। তাদের অন্য শ্রেণিতে সরান, অথবা এটিকে Inactive করুন।"
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
                    to="projects.settings.suppliers"
                    en="The suppliers each category classifies."
                    bn="যে সরবরাহকারীদের প্রতিটি শ্রেণি ভাগ করে।"
                />
            </GuideSection>
        </Guide>
    );
}
