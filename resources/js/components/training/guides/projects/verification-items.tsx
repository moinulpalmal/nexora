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
 * Projects → Settings → Verification Items (`projects.settings.verification-items.index`), with its
 * New verification item / Edit dialog and the Deleted tab. Source:
 * documentation/projects.md §4 (Verification items).
 */
export default function VerificationItemsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The verification items of the company you are working in — a reusable list of things the company checks, each with a name and an optional description. Each company keeps its own list, and it starts empty."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার যাচাইয়ের বিষয়গুলো — কোম্পানি যেসব বিষয় যাচাই করে তার একটি পুনর্ব্যবহারযোগ্য তালিকা, প্রতিটির একটি নাম ও ঐচ্ছিক বিবরণ থাকে। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে, এবং তালিকাটি খালি অবস্থায় শুরু হয়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.settings.verification-items.view</Ui>{' '}
                            opens the list;{' '}
                            <Ui>projects.settings.verification-items.create</Ui>
                            ,{' '}
                            <Ui>projects.settings.verification-items.update</Ui>{' '}
                            and{' '}
                            <Ui>projects.settings.verification-items.delete</Ui>{' '}
                            add, edit and delete;{' '}
                            <Ui>
                                projects.settings.verification-items.restore
                            </Ui>{' '}
                            and{' '}
                            <Ui>
                                projects.settings.verification-items.force-delete
                            </Ui>{' '}
                            work on the Deleted tab. The project-manager role
                            holds them all. You also need a company to work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.settings.verification-items.view</Ui>{' '}
                            থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>projects.settings.verification-items.create</Ui>
                            ,{' '}
                            <Ui>projects.settings.verification-items.update</Ui>{' '}
                            ও{' '}
                            <Ui>projects.settings.verification-items.delete</Ui>{' '}
                            দিয়ে যোগ, সম্পাদনা ও মোছা যায়;{' '}
                            <Ui>
                                projects.settings.verification-items.restore
                            </Ui>{' '}
                            ও{' '}
                            <Ui>
                                projects.settings.verification-items.force-delete
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
                    en="Check the company switcher in the header: the list, and every item you add, belongs to the company shown there."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যে বিষয় যোগ করবেন সবই সেখানে দেখানো কোম্পানির।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Settings → Verification Items. The description under the title names the company the list belongs to. Search by name in the row under the headings."
                        bn="Projects → Settings → Verification Items খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে। শিরোনামের নিচের সারিতে নাম দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The verification items of the selected company."
                            bn="বেছে নেওয়া কোম্পানির যাচাইয়ের বিষয়গুলো।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New verification item</Ui>, enter a
                                name, and save with <Ui>Save &amp; close</Ui> —
                                or <Ui>Save &amp; add another</Ui> to keep
                                going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New verification item</Ui> চাপুন, একটি নাম
                                দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে সেভ
                                করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New verification item: name, status and description."
                            bn="New verification item: নাম, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="An item you no longer use can be made Inactive, or deleted. To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="যে বিষয়টি আর ব্যবহার করেন না সেটিকে Inactive করা বা মোছা যায়। একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted item waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা বিষয় Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted item included. There is no code: the name is how an item is told apart."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা বিষয়ও এর মধ্যে ধরা হয়। কোনো কোড নেই: নাম দিয়েই একটি বিষয়কে আলাদা করা হয়।"
                    />
                    <Field
                        label="Description"
                        en="Optional notes about what is checked and how. Shown in the form, not in the list."
                        bn="কী এবং কীভাবে যাচাই করা হয় সে সম্পর্কে ঐচ্ছিক নোট। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the item is in use:"
                        bn="বিষয়টি ব্যবহারে আছে কি না:"
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
                        en="There is no company field: an item always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: বিষয় সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: items are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: বিষয়গুলো একটি একটি করে যোগ করতে হয়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='A verification item named "Trade licence checked" already exists in this company.'
                    en="The name is taken in this company, whatever the capitals. The same name in another company is fine."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, এই কোম্পানিতে নামটি আগেই নেওয়া হয়েছে। অন্য কোম্পানিতে একই নাম থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A deleted verification item in this company is named "Trade licence checked". Restore it from the Deleted tab instead.'
                    en="A deleted item still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি বিষয়ের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Select a company in the header before adding a verification item."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding items while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় বিষয় যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ করার আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="A company that still has verification items — deleted ones included — cannot be deleted. Make the company Inactive instead, or delete its items for good first."
                    bn="যে কোম্পানিতে এখনো যাচাইয়ের বিষয় আছে — মুছে ফেলাগুলোসহ — সেটি মোছা যায় না। তার বদলে কোম্পানিটিকে Inactive করুন, অথবা আগে এর বিষয়গুলো চিরতরে মুছুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.companies"
                    en="The companies verification items belong to."
                    bn="যেসব কোম্পানির অধীনে যাচাইয়ের বিষয়গুলো থাকে।"
                />
                <RelatedGuide
                    to="projects.settings.certifications"
                    en="The standard certifications every company shares."
                    bn="সব কোম্পানির জন্য একই সাধারণ সার্টিফিকেশনগুলো।"
                />
            </GuideSection>
        </Guide>
    );
}
