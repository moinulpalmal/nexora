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
 * HRM → Departments (`hrm.departments.index`), with its New department / Edit
 * dialog, the Deleted tab and the spreadsheet import. Source:
 * documentation/hrm.md §4.
 */
export default function DepartmentsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The departments of the company you are working in — Human Resources, Production, Finance and so on. Employees and other HRM records will point at them. Each company keeps its own list, so two companies may both have a department coded HR."
                    bn="আপনি যে কোম্পানিতে কাজ করছেন তার বিভাগগুলো — Human Resources, Production, Finance ইত্যাদি। কর্মী ও HRM-এর অন্যান্য রেকর্ড এগুলোর সাথে যুক্ত থাকবে। প্রতিটি কোম্পানির নিজস্ব তালিকা থাকে, তাই দুটি কোম্পানিতেই HR কোডের বিভাগ থাকতে পারে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>hrm.departments.view</Ui> opens the list;{' '}
                            <Ui>hrm.departments.create</Ui>,{' '}
                            <Ui>hrm.departments.update</Ui> and{' '}
                            <Ui>hrm.departments.delete</Ui> add, edit and
                            delete; <Ui>hrm.departments.restore</Ui> and{' '}
                            <Ui>hrm.departments.force-delete</Ui> work on the
                            Deleted tab; <Ui>hrm.departments.import</Ui> loads
                            them from a spreadsheet. You also need a company to
                            work in.
                        </>
                    }
                    bn={
                        <>
                            <Ui>hrm.departments.view</Ui> থাকলে তালিকা খোলা
                            যায়; <Ui>hrm.departments.create</Ui>,{' '}
                            <Ui>hrm.departments.update</Ui> ও{' '}
                            <Ui>hrm.departments.delete</Ui> দিয়ে যোগ, সম্পাদনা
                            ও মোছা যায়; <Ui>hrm.departments.restore</Ui> ও{' '}
                            <Ui>hrm.departments.force-delete</Ui> Deleted ট্যাবে
                            কাজ করে; <Ui>hrm.departments.import</Ui> দিয়ে
                            স্প্রেডশিট থেকে আনা যায়। এর সাথে কাজ করার জন্য একটি
                            কোম্পানিও লাগবে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Check the company switcher in the header: the list, and everything you add, belongs to the company shown there. If the screen says to select a company, choose one from the switcher; if it says you have none, ask an administrator to give you one."
                    bn="হেডারের কোম্পানি সুইচারটি দেখে নিন: তালিকা এবং আপনি যা যোগ করবেন সবই সেখানে দেখানো কোম্পানির। স্ক্রিনে কোম্পানি বেছে নিতে বললে সুইচার থেকে একটি বেছে নিন; যদি বলে আপনার কোনো কোম্পানি নেই, তাহলে একজন অ্যাডমিনিস্ট্রেটরকে একটি দিতে বলুন।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open HRM → Departments. The description under the title names the company the list belongs to."
                        bn="HRM → Departments খুলুন। শিরোনামের নিচের বর্ণনায় তালিকাটি কোন কোম্পানির তা লেখা থাকে।"
                    >
                        <Shot
                            id="overview"
                            en="The departments of the selected company, with the company switcher in the header."
                            bn="বেছে নেওয়া কোম্পানির বিভাগগুলো, হেডারে কোম্পানি সুইচারসহ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New department</Ui>, enter a code and
                                a name, and save with <Ui>Save &amp; close</Ui>{' '}
                                — or <Ui>Save &amp; add another</Ui> to keep
                                going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New department</Ui> চাপুন, একটি কোড ও নাম
                                দিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে সেভ
                                করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New department: code, name and status."
                            bn="New department: কোড, নাম ও অবস্থা।"
                        />
                    </Step>
                    <Step
                        en="To delete several at once, tick their rows — the box in the heading ticks every row on the page — and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিন — শিরোনামের বক্সটি পেজের সব সারিতে টিক দেয় — তারপর যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                    <Step
                        en="A deleted department waits on the Deleted tab, where it can be restored or deleted for good — one at a time, or ticked together and removed with Delete permanently."
                        bn="মুছে ফেলা বিভাগ Deleted ট্যাবে থাকে, সেখান থেকে এটি ফিরিয়ে আনা বা চিরতরে মোছা যায় — একটি একটি করে, অথবা কয়েকটিতে টিক দিয়ে Delete permanently চেপে একসাথে।"
                    />
                </Steps>

                <SubHeading
                    en="Loading many departments from a spreadsheet"
                    bn="স্প্রেডশিট থেকে একসাথে অনেক বিভাগ আনা"
                />
                <Steps>
                    <Step
                        en={
                            <>
                                Press <Ui>Template</Ui> for an empty workbook
                                with the right headings, fill one row per
                                department, then press <Ui>Import</Ui> and
                                upload it.
                            </>
                        }
                        bn={
                            <>
                                সঠিক শিরোনামসহ একটি খালি ওয়ার্কবুক পেতে{' '}
                                <Ui>Template</Ui> চাপুন, প্রতিটি বিভাগের জন্য
                                একটি সারি পূরণ করুন, তারপর <Ui>Import</Ui> চেপে
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
                        en="Check which column feeds Code, Name and Status, and choose what happens to a row that matches a department you already have: skip it or update it. A row matches when its code or its name is already used."
                        bn="কোন কলাম থেকে Code, Name ও Status আসবে তা মিলিয়ে নিন, আর আগে থেকে থাকা কোনো বিভাগের সাথে মিলে যাওয়া সারির কী হবে তা বেছে নিন: বাদ দেওয়া হবে, নাকি হালনাগাদ করা হবে। কোনো সারির কোড বা নাম আগে থেকেই ব্যবহৃত থাকলে সেটিকে মিল হিসেবে ধরা হয়।"
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
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as HR or PROD-1. Saved in capitals. No two departments of the company may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন HR বা PROD-1। বড় হাতের অক্ষরে সেভ হয়। কোম্পানির দুটি বিভাগের একই কোড হতে পারে না — মুছে ফেলা বিভাগও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique within the company whatever the capitals, a deleted department included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, কোম্পানির মধ্যে অনন্য হতে হবে — মুছে ফেলা বিভাগও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the department is in use:"
                        bn="বিভাগটি ব্যবহারে আছে কি না:"
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
                        en="There is no company field: a department always belongs to the company selected in the header."
                        bn="কোনো কোম্পানি ঘর নেই: বিভাগ সবসময় হেডারে বেছে নেওয়া কোম্পানির অধীনে যায়।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "HR" is already taken in this company.'
                    en="Another department of this company uses the code, perhaps in small letters. The same code in another company is fine."
                    bn="এই কোম্পানির অন্য একটি বিভাগ কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে। অন্য কোম্পানিতে একই কোড থাকলে সমস্যা নেই।"
                />
                <Message
                    text='A department named "Human Resources" already exists in this company.'
                    en="The name is taken in this company, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, এই কোম্পানিতে নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted department in this company is named "Human Resources". Restore it from the Deleted tab instead.'
                    en="A deleted department still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি বিভাগের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
                <Message
                    text="Used by 3 employees. Move them to another department, or deactivate this one instead."
                    en="Employees are in the department — deleted employees count too — so it cannot be deleted. Move them in HRM → Employees, or make the department Inactive. In a bulk delete the others still go."
                    bn="বিভাগটিতে কর্মচারী আছেন — মুছে ফেলা কর্মচারীও ধরা হয় — তাই এটি মোছা যায় না। HRM → Employees-এ তাঁদের অন্য বিভাগে সরান, অথবা বিভাগটিকে Inactive করুন। একসাথে মোছার সময় বাকিগুলো ঠিকই মুছে যায়।"
                />
                <Message
                    text="Select a company in the header before adding a department."
                    en="No company is selected. Choose one from the switcher and try again."
                    bn="কোনো কোম্পানি বেছে নেওয়া হয়নি। সুইচার থেকে একটি বেছে নিয়ে আবার চেষ্টা করুন।"
                />
                <Message
                    text='The code "HR" belongs to "Human Resources" but the name "Finance" belongs to the department coded "FIN". Correct one of them.'
                    en="An import row points at two different departments. Fix the code or the name in the sheet and upload the row again."
                    bn="ইমপোর্টের একটি সারি দুটি ভিন্ন বিভাগকে নির্দেশ করছে। শিটে কোড বা নাম ঠিক করে সারিটি আবার আপলোড করুন।"
                />
                <Message
                    text="This file was uploaded for another company. Switch back to it in the header to finish the import, or cancel it."
                    en="You changed company after uploading. The rows can only go into the company they were uploaded for."
                    bn="আপলোডের পর আপনি কোম্পানি বদলেছেন। সারিগুলো শুধু সেই কোম্পানিতেই যেতে পারে যার জন্য আপলোড করা হয়েছিল।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Adding departments while the wrong company is selected puts them in that company — nothing warns you. Look at the switcher and the page description before saving or importing."
                    bn="ভুল কোম্পানি বেছে নেওয়া অবস্থায় বিভাগ যোগ করলে সেগুলো সেই কোম্পানিতেই চলে যায় — কোনো সতর্কবার্তা আসে না। সেভ বা ইমপোর্টের আগে সুইচার ও পেজের বর্ণনা দেখে নিন।"
                />
                <Callout
                    tone="warn"
                    en="Choosing Update on an import overwrites the matched departments' codes and names with the sheet's. Use Skip when the sheet is only meant to add new ones."
                    bn="ইমপোর্টে Update বেছে নিলে মিলে যাওয়া বিভাগগুলোর কোড ও নাম শিটের মান দিয়ে বদলে যায়। শিট দিয়ে শুধু নতুন বিভাগ যোগ করতে চাইলে Skip বেছে নিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="hrm.employees"
                    en="Placing employees in a department."
                    bn="কর্মচারীদের বিভাগে রাখা।"
                />
                <RelatedGuide
                    to="admin.companies"
                    en="The companies departments belong to."
                    bn="যেসব কোম্পানির অধীনে বিভাগগুলো থাকে।"
                />
                <RelatedGuide
                    to="admin.users"
                    en="Giving someone a company to work in."
                    bn="কাউকে কাজের জন্য একটি কোম্পানি দেওয়া।"
                />
            </GuideSection>
        </Guide>
    );
}
