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
 * Projects → Units of Measure (`projects.units.index`), with its New unit /
 * Edit dialog and the Deleted tab. Source: documentation/projects.md §4
 * (Units of measure).
 */
export default function UnitsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="The units quantities are counted in — pieces, kilograms, square metres, hours and the like. There is one list for the whole group: every company sees the same units, so an edit or a delete shows in all of them. A new installation already holds a standard set of 27 common units."
                    bn="যে এককে পরিমাণ গোনা হয় — পিস, কিলোগ্রাম, বর্গমিটার, ঘণ্টা ইত্যাদি। পুরো গ্রুপের জন্য তালিকা একটিই: প্রতিটি কোম্পানি একই একক দেখে, তাই কোনো সম্পাদনা বা মোছা সবগুলো কোম্পানিতেই দেখা যায়। নতুন ইনস্টলেশনে আগে থেকেই ২৭টি প্রচলিত এককের একটি মানক তালিকা থাকে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>projects.units.view</Ui> opens the list;{' '}
                            <Ui>projects.units.create</Ui>,{' '}
                            <Ui>projects.units.update</Ui> and{' '}
                            <Ui>projects.units.delete</Ui> add, edit and delete;{' '}
                            <Ui>projects.units.restore</Ui> and{' '}
                            <Ui>projects.units.force-delete</Ui> work on the
                            Deleted tab. The project-manager role may view, add
                            and edit, but not delete or restore: the list is
                            shared, so removing a unit is left to the super
                            admin. No company needs to be selected.
                        </>
                    }
                    bn={
                        <>
                            <Ui>projects.units.view</Ui> থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>projects.units.create</Ui>,{' '}
                            <Ui>projects.units.update</Ui> ও{' '}
                            <Ui>projects.units.delete</Ui> দিয়ে যোগ, সম্পাদনা ও
                            মোছা যায়; <Ui>projects.units.restore</Ui> ও{' '}
                            <Ui>projects.units.force-delete</Ui> Deleted ট্যাবে
                            কাজ করে। project-manager রোল দেখতে, যোগ করতে ও
                            সম্পাদনা করতে পারে, কিন্তু মুছতে বা ফিরিয়ে আনতে
                            পারে না: তালিকাটি সবার, তাই একক মোছার কাজ সুপার
                            অ্যাডমিনের। কোনো কোম্পানি বেছে নেওয়ার দরকার নেই।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Look through the standard units first — the one you need is probably there already. Agree any new unit with the other companies: the list is shared, so a unit made here is offered to everyone."
                    bn="আগে মানক এককগুলো দেখে নিন — যেটি দরকার সেটি সম্ভবত আগে থেকেই আছে। নতুন কোনো একক অন্য কোম্পানিগুলোর সাথে ঠিক করে নিন: তালিকাটি সবার, তাই এখানে তৈরি করা একক সবার জন্য দেখানো হয়।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Projects → Units of Measure. Search by code, name or symbol in the row under the headings."
                        bn="Projects → Units of Measure খুলুন। শিরোনামের নিচের সারিতে কোড, নাম বা প্রতীক দিয়ে খুঁজুন।"
                    >
                        <Shot
                            id="overview"
                            en="The standard units every company shares."
                            bn="সব কোম্পানির জন্য একই মানক একক।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New unit</Ui>, enter a code, a name
                                and, if it prints differently, a symbol; choose
                                the decimal places, and save with{' '}
                                <Ui>Save &amp; close</Ui> — or{' '}
                                <Ui>Save &amp; add another</Ui> to keep going.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New unit</Ui> চাপুন, একটি কোড ও নাম দিন, আর
                                ছাপায় ভিন্ন দেখালে একটি প্রতীক দিন; দশমিকের ঘর
                                বেছে নিন, তারপর <Ui>Save &amp; close</Ui> দিয়ে
                                সেভ করুন — অথবা আরও যোগ করতে{' '}
                                <Ui>Save &amp; add another</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="form"
                            en="New unit: code, name, symbol, decimal places, status and description."
                            bn="New unit: কোড, নাম, প্রতীক, দশমিকের ঘর, অবস্থা ও বিবরণ।"
                        />
                    </Step>
                    <Step
                        en="To stop offering a unit without losing it, edit it and make it Inactive."
                        bn="কোনো একক হারিয়ে না ফেলে সেটি দেখানো বন্ধ করতে সম্পাদনা করে Inactive করুন।"
                    />
                    <Step
                        en="To delete several at once, tick their rows and press Delete in the bar that appears."
                        bn="একসাথে কয়েকটি মুছতে সারিগুলোতে টিক দিয়ে যে বারটি দেখা যায় তাতে Delete চাপুন।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Code"
                        en="Up to 20 letters and digits, optionally joined by hyphens or underscores, such as KG or SQM. Saved in capitals. No two units may share a code — a deleted one included."
                        bn="সর্বোচ্চ ২০টি অক্ষর ও সংখ্যা, চাইলে হাইফেন বা আন্ডারস্কোর দিয়ে যুক্ত, যেমন KG বা SQM। বড় হাতের অক্ষরে সেভ হয়। দুটি এককের একই কোড হতে পারে না — মুছে ফেলা এককও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Name"
                        en="Up to 100 characters; extra spaces are removed. Unique whatever the capitals, a deleted unit included."
                        bn="সর্বোচ্চ ১০০ অক্ষর; বাড়তি ফাঁকা জায়গা বাদ দেওয়া হয়। বড়-ছোট অক্ষর যা-ই হোক, অনন্য হতে হবে — মুছে ফেলা এককও এর মধ্যে ধরা হয়।"
                    />
                    <Field
                        label="Symbol"
                        en="Optional, up to 10 characters: how the unit is printed, such as kg or m². Kept exactly as typed, small letters included. Two units may share a symbol."
                        bn="ঐচ্ছিক, সর্বোচ্চ ১০ অক্ষর: এককটি কীভাবে ছাপা হয়, যেমন kg বা m²। যেভাবে লেখা হয় ঠিক সেভাবেই রাখা হয়, ছোট হাতের অক্ষরসহ। দুটি এককের একই প্রতীক হতে পারে।"
                    />
                    <Field
                        label="Decimal places"
                        en="0 to 6, 2 if left alone: how many decimals a quantity in this unit takes — 0 for pieces, 3 for kilograms. Quantity fields that use the unit will follow it."
                        bn="০ থেকে ৬, কিছু না বদলালে ২: এই এককের পরিমাণে কয় ঘর দশমিক থাকে — পিসের জন্য ০, কিলোগ্রামের জন্য ৩। যে পরিমাণের ঘরগুলো এই একক ব্যবহার করবে সেগুলো এটি মেনে চলবে।"
                    />
                    <Field
                        label="Description"
                        en="Optional note, up to 255 characters. Shown in the form, not in the list."
                        bn="ঐচ্ছিক নোট, সর্বোচ্চ ২৫৫ অক্ষর। ফর্মে দেখা যায়, তালিকায় নয়।"
                    />
                    <Field
                        label="Status"
                        en="Whether the unit is offered:"
                        bn="এককটি দেখানো হয় কি না:"
                    />
                </Fields>
                <EnumOptions
                    labels={RECORD_STATUS_LABELS}
                    explain={{
                        A: {
                            en: 'Offered for new entries.',
                            bn: 'নতুন এন্ট্রির জন্য দেখানো হয়।',
                        },
                        I: {
                            en: 'Kept on record, but no longer offered.',
                            bn: 'রেকর্ডে থাকে, কিন্তু আর দেখানো হয় না।',
                        },
                    }}
                />
                <Bullets>
                    <Bullet
                        en="There is no company field: every company shares the same units."
                        bn="কোনো কোম্পানি ঘর নেই: সব কোম্পানি একই একক ব্যবহার করে।"
                    />
                    <Bullet
                        en="There is no spreadsheet import: units beyond the standard set are added one by one."
                        bn="স্প্রেডশিট ইমপোর্ট নেই: মানক তালিকার বাইরের একক একটি একটি করে যোগ করতে হয়।"
                    />
                    <Bullet
                        en="A standard unit you rename, deactivate or delete stays that way — the standard set is only ever added to, never rewritten."
                        bn="কোনো মানক এককের নাম বদলালে, নিষ্ক্রিয় করলে বা মুছলে তা সেভাবেই থাকে — মানক তালিকায় শুধু যোগ হয়, কখনো আবার লেখা হয় না।"
                    />
                </Bullets>
                <ListBasics deletedTab />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text='The code "KG" is already taken.'
                    en="Another unit uses the code, perhaps in small letters."
                    bn="অন্য একটি একক কোডটি ব্যবহার করছে, হয়তো ছোট হাতের অক্ষরে।"
                />
                <Message
                    text='A unit named "Kilogram" already exists.'
                    en="The name is taken, whatever the capitals."
                    bn="বড়-ছোট অক্ষর যা-ই হোক, নামটি আগেই নেওয়া হয়েছে।"
                />
                <Message
                    text='A deleted unit is named "Kilogram". Restore it from the Deleted tab instead.'
                    en="A deleted unit still holds the name. Restore it rather than making a second."
                    bn="মুছে ফেলা একটি এককের এখনো এই নামটি আছে। দ্বিতীয়টি না বানিয়ে সেটিকেই ফিরিয়ে আনুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="Renaming, deactivating or deleting a unit changes it for every company, not only yours. Do not change a unit's meaning — make a new unit instead."
                    bn="এককের নাম বদলানো, নিষ্ক্রিয় করা বা মোছা শুধু আপনার নয়, সব কোম্পানির জন্যই তা বদলে দেয়। কোনো এককের অর্থ বদলাবেন না — তার বদলে নতুন একক তৈরি করুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="projects.tender-types"
                    en="The other list every company shares in Projects."
                    bn="Projects-এর আরেকটি তালিকা যা সব কোম্পানি ভাগ করে।"
                />
            </GuideSection>
        </Guide>
    );
}
