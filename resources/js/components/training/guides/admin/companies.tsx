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
 * Admin → Companies (`admin.companies.index`), with its New company / Edit
 * dialog and the Deleted tab. Source: documentation/admin.md §10.
 */
export default function CompaniesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The list of your organization's companies. Every Projects, Accounting and HRM record belongs to one of them, and people work in one company at a time, chosen from the switcher in the header. This screen is where companies are added, renamed, retired and, when nothing belongs to them, deleted."
                    bn="আপনার প্রতিষ্ঠানের কোম্পানিগুলোর তালিকা। Projects, Accounting ও HRM-এর প্রতিটি রেকর্ড এগুলোর কোনো একটির অধীনে থাকে, আর সবাই একবারে একটি কোম্পানিতে কাজ করেন — হেডারের সুইচার থেকে সেটি বেছে নেন। এই স্ক্রিনে কোম্পানি যোগ, নাম বদল, অবসরে পাঠানো এবং — যখন কোনো রেকর্ড এর অধীনে নেই — মোছা হয়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.companies.view</Ui> opens the list;{' '}
                            <Ui>admin.companies.create</Ui>,{' '}
                            <Ui>admin.companies.update</Ui> and{' '}
                            <Ui>admin.companies.delete</Ui> add, edit and
                            delete. <Ui>admin.companies.restore</Ui> and{' '}
                            <Ui>admin.companies.force-delete</Ui> work on the
                            Deleted tab.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.companies.view</Ui> থাকলে তালিকা খোলা
                            যায়; <Ui>admin.companies.create</Ui>,{' '}
                            <Ui>admin.companies.update</Ui> ও{' '}
                            <Ui>admin.companies.delete</Ui> দিয়ে যোগ, সম্পাদনা
                            ও মোছা যায়। <Ui>admin.companies.restore</Ui> ও{' '}
                            <Ui>admin.companies.force-delete</Ui> Deleted ট্যাবে
                            কাজ করে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Adding a company does not let anyone work in it yet. Each person is given their companies on the Users screen; only the super admin can work in every company without being given it."
                    bn="কোম্পানি যোগ করলেই কেউ সেখানে কাজ করতে পারেন না। প্রত্যেককে Users স্ক্রিন থেকে তাঁর কোম্পানিগুলো দিতে হয়; শুধু super admin কাউকে দেওয়া ছাড়াই সব কোম্পানিতে কাজ করতে পারেন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Admin → Companies. Each row shows the short code, the name, how many users were given the company, and whether it is active."
                        bn="Admin → Companies খুলুন। প্রতিটি সারিতে থাকে সংক্ষিপ্ত কোড, নাম, কতজন ব্যবহারকারীকে কোম্পানিটি দেওয়া হয়েছে এবং এটি সক্রিয় কি না।"
                    >
                        <Shot
                            id="overview"
                            en="The companies, each with its short code, users and status."
                            bn="কোম্পানিগুলো, প্রতিটির সংক্ষিপ্ত কোড, ব্যবহারকারী ও অবস্থাসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New company</Ui>, enter the name and a
                                short code such as <Ui>ATL</Ui>, and save with{' '}
                                <Ui>Save &amp; close</Ui>.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New company</Ui> চাপুন, নাম ও একটি সংক্ষিপ্ত
                                কোড দিন, যেমন <Ui>ATL</Ui>, তারপর{' '}
                                <Ui>Save &amp; close</Ui> দিয়ে সেভ করুন।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New company: name, short code and status."
                            bn="New company: নাম, সংক্ষিপ্ত কোড ও অবস্থা।"
                        />
                    </Step>
                    <Step
                        en="To retire a company, edit it and set its status to Inactive. Nobody can select it any more, and its records stay."
                        bn="কোনো কোম্পানিকে অবসরে পাঠাতে এটি সম্পাদনা করে অবস্থা Inactive করুন। তখন আর কেউ এটি বেছে নিতে পারবেন না, তবে এর রেকর্ডগুলো থেকে যাবে।"
                    />
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears. A company that departments belong to cannot be ticked."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন। যে কোম্পানির অধীনে বিভাগ আছে সেটিতে টিক দেওয়া যায় না।"
                    />
                    <Step
                        en="A deleted company waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা কোম্পানি Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
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
                        en="The company's full name, up to 150 characters. Extra spaces are removed. No two companies may share a name, whatever the capitals — a deleted company included."
                        bn="কোম্পানির পূর্ণ নাম, সর্বোচ্চ ১৫০ অক্ষর। বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট হাতের অক্ষর যা-ই হোক, দুটি কোম্পানির একই নাম হতে পারে না — মুছে ফেলা কোম্পানিও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Short code"
                        en="Up to 10 letters and digits, optionally joined by hyphens or underscores. Saved in capitals and shown in the header switcher. Unique in the same way as the name."
                        bn="সর্বোচ্চ ১০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত। বড় হাতের অক্ষরে সেভ হয় এবং হেডারের সুইচারে দেখায়। নামের মতোই এটিও অনন্য হতে হবে।"
                    />
                    <Field
                        label="Status"
                        en="Whether people can select the company:"
                        bn="মানুষ কোম্পানিটি বেছে নিতে পারবেন কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Can be selected in the header by anyone given it.',
                            bn: 'যাঁকে দেওয়া হয়েছে তিনি হেডার থেকে এটি বেছে নিতে পারেন।',
                        },
                        I: {
                            en: 'Kept with all its records, but nobody can select it. Anyone working in it is moved on.',
                            bn: 'সব রেকর্ডসহ রাখা থাকে, কিন্তু কেউ এটি বেছে নিতে পারেন না। যিনি এতে কাজ করছিলেন তাঁকে সরিয়ে দেওয়া হয়।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="The delete button is disabled while any department belongs to the company; point at it to read why."
                        bn="কোম্পানির অধীনে কোনো বিভাগ থাকলে মোছার বোতাম বন্ধ থাকে; কারণ জানতে এর ওপর মাউস রাখুন।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='A company named "Acme Trading Ltd." already exists.'
                    en="Another company has this name, perhaps in other capitals. Use a different name or edit that company."
                    bn="অন্য কোনো কোম্পানির এই নাম আছে, হয়তো ভিন্ন বড়-ছোট অক্ষরে। অন্য নাম দিন অথবা সেই কোম্পানিটি সম্পাদনা করুন।"
                />
                <Message
                    text='A deleted company is named "Acme Trading Ltd.". Restore it from the Deleted tab instead.'
                    en="A deleted company still holds the name. Restore that one rather than making a second."
                    bn="মুছে ফেলা একটি কোম্পানির এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text='The short code "ATL" is already taken.'
                    en="Another company uses this short code. Choose another."
                    bn="অন্য কোনো কোম্পানি এই সংক্ষিপ্ত কোড ব্যবহার করছে। অন্যটি বেছে নিন।"
                />
                <Message
                    text="3 departments belong to this company. Deactivate the company instead, or delete them for good first."
                    en="A company cannot be deleted while anything belongs to it — deleted departments count too. Make it inactive instead."
                    bn="কোনো কিছু অধীনে থাকা অবস্থায় কোম্পানি মোছা যায় না — মুছে ফেলা বিভাগও গোনা হয়। তার বদলে এটিকে inactive করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Renaming a company or changing its short code changes it everywhere at once — on every record, in every switcher. Correct a typo, but do not reuse a company for a different business."
                    bn="কোম্পানির নাম বা সংক্ষিপ্ত কোড বদলালে তা সব জায়গায় একসাথে বদলে যায় — প্রতিটি রেকর্ডে, প্রতিটি সুইচারে। বানান ভুল ঠিক করুন, কিন্তু কোনো কোম্পানিকে অন্য ব্যবসার জন্য আবার ব্যবহার করবেন না।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.users"
                    en="Giving people the companies they work in."
                    bn="মানুষকে তাঁদের কাজের কোম্পানিগুলো দেওয়া।"
                />
                <RelatedGuide
                    to="hrm.departments"
                    en="The departments that belong to each company."
                    bn="প্রতিটি কোম্পানির অধীনের বিভাগগুলো।"
                />
            </GuideSection>
        </Guide>
    );
}
